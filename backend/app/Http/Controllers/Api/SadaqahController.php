<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SadaqahProject;
use App\Models\SadaqahContribution;
use App\Models\WalletTransaction;
use App\Models\Setting;
use App\Services\MonnifyService;
use App\Services\OpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SadaqahController extends Controller
{
    public function index()
    {
        $projects = SadaqahProject::where('active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($projects);
    }

    public function show($id)
    {
        $project = SadaqahProject::with(['contributions' => function($query) {
            $query->where('status', 'success')->where('is_anonymous', false)->with('user:id,name')->limit(10);
        }])->findOrFail($id);

        return response()->json($project);
    }

    public function contribute(Request $request, $id)
    {
        $project = SadaqahProject::findOrFail($id);
        if (!$project->active) {
            return response()->json(['message' => 'This project is no longer accepting contributions.'], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'is_anonymous' => 'boolean',
            'gateway' => 'nullable|in:paystack,flutterwave,monnify,opay,wallet',
            'callback_url' => 'nullable|url',
        ]);

        $user = $request->user();
        $amount = round($validated['amount'], 2);

        $gateway = strtolower($request->input('gateway', 'paystack'));

        if ($gateway !== 'wallet' && !Setting::get("gateway_{$gateway}_enabled", true)) {
            return response()->json(['message' => "The selected payment gateway ($gateway) is currently disabled. Please try another method."], 422);
        }

        if ($gateway === 'wallet') {
            return $this->processWalletContribution($user, $project, $amount, $validated['is_anonymous'] ?? false);
        }

        if ($gateway === 'monnify') {
            $reference = 'SADAQAH_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));
            SadaqahContribution::create([
                'user_id' => $user->id,
                'sadaqah_project_id' => $project->id,
                'amount' => $amount,
                'reference' => $reference,
                'is_anonymous' => $validated['is_anonymous'] ?? false,
                'status' => 'pending',
            ]);
            return $this->initiateMonnify($user, $amount, $reference, $request->input('callback_url'));
        }

        if ($gateway === 'opay') {
            $reference = 'SADAQAH_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));
            SadaqahContribution::create([
                'user_id' => $user->id,
                'sadaqah_project_id' => $project->id,
                'amount' => $amount,
                'reference' => $reference,
                'is_anonymous' => $validated['is_anonymous'] ?? false,
                'status' => 'pending',
            ]);
            return $this->initiateOpay($user, $amount, $reference, $request->input('callback_url'));
        }

        $reference = 'SADAQAH_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        $contribution = SadaqahContribution::create([
            'user_id' => $user->id,
            'sadaqah_project_id' => $project->id,
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'pending',
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        if ($gateway === 'flutterwave') {
            return $this->initiateFlutterwave($user, $amount, $reference, $validated['callback_url'] ?? null);
        }

        return $this->initiatePaystack($user, $amount, $reference, $validated['callback_url'] ?? null);
    }

    public function myContributions(Request $request)
    {
        $contributions = SadaqahContribution::with('project:id,name')
            ->where('user_id', $request->user()->id)
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($contributions);
    }

    protected function initiatePaystack($user, $amount, $reference, $callbackUrl)
    {
        $secret = config('services.paystack.secret_key');
        if (!$secret) {
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        $payload = [
            'email' => $user->email,
            'amount' => (int) round($amount * 100),
            'reference' => $reference,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user->id,
                'sadaqah' => true,
            ],
        ];
        if ($callbackUrl) {
            $payload['callback_url'] = $callbackUrl;
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.paystack.co/transaction/initialize', $payload);

        if (!$response->ok() || !($response->json('status') === true)) {
            Log::error('Paystack Sadaqah initialize failed', ['reference' => $reference, 'body' => $response->json()]);
            return response()->json(['message' => 'Failed to initialize payment'], 502);
        }

        $data = $response->json('data');
        return response()->json([
            'authorization_url' => $data['authorization_url'],
            'reference' => $reference,
            'amount' => $amount,
        ]);
    }

    protected function initiateFlutterwave($user, $amount, $reference, $callbackUrl)
    {
        $secret = config('services.flutterwave.secret_key');
        if (!$secret) {
            return response()->json(['message' => 'Payment provider not configured'], 500);
        }

        $payload = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => 'NGN',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'meta' => [
                'user_id' => $user->id,
                'sadaqah' => true,
            ],
        ];
        if ($callbackUrl) {
            $payload['redirect_url'] = $callbackUrl;
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        if (!$response->ok() || ($response->json('status') !== 'success')) {
            Log::error('Flutterwave Sadaqah initialize failed', ['reference' => $reference, 'body' => $response->json()]);
            return response()->json(['message' => 'Failed to initialize payment'], 502);
        }

        $data = $response->json('data');
        return response()->json([
            'authorization_url' => $data['link'],
            'reference' => $reference,
            'amount' => $amount,
        ]);
    }

    private function initiateMonnify($user, $amount, $reference, $callbackUrl)
    {
        $service = app(MonnifyService::class);
        $monnifyData = $service->initializeTransaction([
            'amount' => round($amount, 2),
            'customerName' => $user->name,
            'customerEmail' => $user->email,
            'paymentReference' => $reference,
            'paymentDescription' => 'Sadaqah contribution',
            'redirectUrl' => $callbackUrl ?? config('app.url'),
        ]);

        if (!$monnifyData) {
            return response()->json(['message' => 'Failed to initialize Monnify payment'], 502);
        }

        return response()->json([
            'authorization_url' => $monnifyData['checkoutUrl'] ?? null,
            'checkout_url' => $monnifyData['checkoutUrl'] ?? null,
            'reference' => $reference,
            'amount' => $amount,
        ]);
    }

    private function initiateOpay($user, $amount, $reference, $callbackUrl)
    {
        $service = app(OpayService::class);
        $opayData = $service->initializeTransaction([
            'amount' => round($amount, 2),
            'customerName' => $user->name,
            'customerEmail' => $user->email,
            'reference' => $reference,
            'paymentDescription' => 'Sadaqah contribution',
            'callbackUrl' => $callbackUrl ?? config('app.url'),
        ]);

        if (!$opayData) {
            return response()->json(['message' => 'Failed to initialize Opay payment'], 502);
        }

        return response()->json([
            'authorization_url' => $opayData['cashierUrl'] ?? null,
            'checkout_url' => $opayData['cashierUrl'] ?? null,
            'reference' => $reference,
            'amount' => $amount,
        ]);
    }

    protected function processWalletContribution($user, $project, $amount, $isAnonymous)
    {
        if ($user->balance < $amount) {
            return response()->json(['message' => 'Insufficient wallet balance.'], 422);
        }

        $reference = 'SADAQAH_W_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $project, $amount, $reference, $isAnonymous) {
            // Deduct from wallet
            $user->decrement('balance', $amount);

            // Record wallet transaction
            \App\Models\WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'source' => 'sadaqah_contribution',
                'meta' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                ],
            ]);

            // Create contribution
            SadaqahContribution::create([
                'user_id' => $user->id,
                'sadaqah_project_id' => $project->id,
                'amount' => $amount,
                'reference' => $reference,
                'status' => 'success',
                'is_anonymous' => $isAnonymous,
            ]);

            // Increment project raised amount
            $project->lockForUpdate()->increment('raised_amount', $amount);
        });

        // Send notification
        try {
            $user->notify(new \App\Notifications\PaymentNotification(
                title: 'Contribution Successful',
                message: "Your contribution of ₦" . number_format($amount, 2) . " to " . $project->name . " was successful. Jazakallah Khair.",
                amount: $amount,
                reference: $reference,
                source: 'sadaqah_contribution'
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send Sadaqah notification', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Contribution successful. Jazakallah Khair.',
            'reference' => $reference,
            'amount' => $amount,
            'success' => true
        ]);
    }
}
