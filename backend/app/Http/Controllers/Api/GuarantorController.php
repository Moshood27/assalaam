<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QardHasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuarantorController extends Controller
{
    /**
     * List guarantor requests for the authenticated user.
     */
    public function listRequests(Request $request)
    {
        $user = $request->user();
        $loans = QardHasan::query()
            ->with(['user.branch', 'guarantors'])
            ->whereHas('guarantors', function ($q) use ($user) {
                $q->where('guarantor_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (QardHasan $loan) use ($user) {
                // Extract the pivot for the current guarantor only
                $pivot = $loan->guarantors->firstWhere('id', $user->id)?->pivot;
                $acceptedCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'accepted')->count() ?? 0);
                $declinedCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'declined')->count() ?? 0);
                $pendingCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'pending')->count() ?? 0);
                $allAccepted = ($pendingCount === 0 && $declinedCount === 0 && $acceptedCount > 0);
                return [
                    'id' => $loan->id,
                    'qard_id_string' => $loan->qard_id_string,
                    'member' => [
                        'id' => $loan->user?->id,
                        'name' => $loan->user?->name,
                        'branch' => $loan->user?->branch?->name,
                    ],
                    'principal_amount' => (float) $loan->principal_amount,
                    'total_installments' => (int) $loan->total_installments,
                    'per_installment' => (float) $loan->per_installment,
                    'status' => $loan->status,
                    'guarantor_status' => $pivot?->status ?? 'pending',
                    'responded_at' => $pivot?->responded_at,
                    'accepted_count' => $acceptedCount,
                    'declined_count' => $declinedCount,
                    'pending_count' => $pendingCount,
                    'all_accepted' => $allAccepted,
                ];
            });

        return response()->json($loans);
    }

    /**
     * Accept a guarantor request for the authenticated user.
     */
    public function accept(Request $request, int $loanId)
    {
        $user = $request->user();

        // Do not allow defaulters to accept
        if ($user->is_defaulter) {
            return response()->json(['message' => 'You are currently marked as a defaulter and cannot guarantee a loan.'], 422);
        }

        $loan = QardHasan::with('guarantors')->findOrFail($loanId);
        $pivot = $loan->guarantors()->where('guarantor_id', $user->id)->first();
        if (!$pivot) {
            return response()->json(['message' => 'Not a guarantor on this loan'], 403);
        }

        $current = $pivot->pivot?->status ?? 'pending';
        if ($current === 'accepted') {
            return response()->json(['message' => 'Already accepted'], 200);
        }
        if ($current === 'declined') {
            return response()->json(['message' => 'You have already declined this request'], 409);
        }

        DB::table('qard_hasan_guarantors')
            ->where('qard_hasan_id', $loan->id)
            ->where('guarantor_id', $user->id)
            ->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

        // Recalculate guarantor decision counts (ensure fresh relations after pivot update)
        $loan->refresh();
        $loan->loadMissing('guarantors', 'user');
        $acceptedCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'accepted')->count() ?? 0);
        $declinedCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'declined')->count() ?? 0);
        $pendingCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'pending')->count() ?? 0);
        $allAccepted = method_exists($loan, 'allGuarantorsAccepted') ? $loan->allGuarantorsAccepted() : ($pendingCount === 0 && $declinedCount === 0 && $acceptedCount > 0);

        // Notify borrower via push (best-effort)
        try {
            if ($loan->user) {
                $push = app(\App\Services\PushService::class);
                $title = 'Guarantor Accepted';
                $body = ($user->name ?: 'A guarantor').' accepted your loan request '.$loan->qard_id_string.'.';
                $token = $loan->user->fcm_token ?: ($loan->user->device_token ?? null);
                $push->send($token, $title, $body, [
                    'type' => 'guarantor_decision',
                    'status' => 'accepted',
                    'loan_id' => $loan->id,
                    'qard_id_string' => $loan->qard_id_string,
                    'guarantor_id' => $user->id,
                    'accepted_count' => $acceptedCount,
                    'declined_count' => $declinedCount,
                    'pending_count' => $pendingCount,
                    'all_accepted' => $allAccepted,
                ]);

                // If all guarantors have accepted, send a follow-up notification to borrower
                if ($allAccepted) {
                    $push->send($token, 'All Guarantors Accepted', 'All selected guarantors have approved your loan '.$loan->qard_id_string.'. Awaiting admin disbursement.', [
                        'type' => 'guarantors_complete',
                        'loan_id' => $loan->id,
                        'qard_id_string' => $loan->qard_id_string,
                        'accepted_count' => $acceptedCount,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'message' => 'Guarantor request accepted',
            'accepted_count' => $acceptedCount,
            'declined_count' => $declinedCount,
            'pending_count' => $pendingCount,
            'all_accepted' => $allAccepted,
        ]);
    }

    /**
     * Decline a guarantor request for the authenticated user.
     */
    public function decline(Request $request, int $loanId)
    {
        $user = $request->user();
        $loan = QardHasan::with('guarantors')->findOrFail($loanId);
        $pivot = $loan->guarantors()->where('guarantor_id', $user->id)->first();
        if (!$pivot) {
            return response()->json(['message' => 'Not a guarantor on this loan'], 403);
        }

        $current = $pivot->pivot?->status ?? 'pending';
        if ($current === 'declined') {
            return response()->json(['message' => 'Already declined'], 200);
        }
        if ($current === 'accepted') {
            return response()->json(['message' => 'You have already accepted this request'], 409);
        }

        DB::table('qard_hasan_guarantors')
            ->where('qard_hasan_id', $loan->id)
            ->where('guarantor_id', $user->id)
            ->update([
                'status' => 'declined',
                'responded_at' => now(),
            ]);

        // Recalculate decision counts
        $loan->loadMissing('guarantors', 'user');
        $acceptedCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'accepted')->count() ?? 0);
        $declinedCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'declined')->count() ?? 0);
        $pendingCount = (int) ($loan->guarantors?->filter(fn($g) => ($g->pivot?->status) === 'pending')->count() ?? 0);
        $allAccepted = method_exists($loan, 'allGuarantorsAccepted') ? $loan->allGuarantorsAccepted() : ($pendingCount === 0 && $declinedCount === 0 && $acceptedCount > 0);

        // Notify borrower via push (best-effort)
        try {
            if ($loan->user) {
                $push = app(\App\Services\PushService::class);
                $title = 'Guarantor Declined';
                $body = ($user->name ?: 'A guarantor').' declined your loan request '.$loan->qard_id_string.'.';
                $token = $loan->user->fcm_token ?: ($loan->user->device_token ?? null);
                $push->send($token, $title, $body, [
                    'type' => 'guarantor_decision',
                    'status' => 'declined',
                    'loan_id' => $loan->id,
                    'qard_id_string' => $loan->qard_id_string,
                    'guarantor_id' => $user->id,
                    'accepted_count' => $acceptedCount,
                    'declined_count' => $declinedCount,
                    'pending_count' => $pendingCount,
                    'all_accepted' => $allAccepted,
                ]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'message' => 'Guarantor request declined',
            'accepted_count' => $acceptedCount,
            'declined_count' => $declinedCount,
            'pending_count' => $pendingCount,
            'all_accepted' => $allAccepted,
        ]);
    }
}
