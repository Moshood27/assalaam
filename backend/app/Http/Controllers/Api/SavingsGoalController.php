<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use App\Models\GoalBooking;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsGoalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $goals = SavingsGoal::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($g) {
                $target = (float) $g->target_amount;
                $saved = (float) $g->saved_amount;
                $progress = $target > 0 ? round(min(100, ($saved / $target) * 100), 2) : 0;
                return array_merge($g->toArray(), [
                    'progress' => $progress,
                    'is_complete' => $saved >= $target,
                ]);
            });
        return response()->json([
            'balance' => (float) $user->balance,
            'goals' => $goals,
            'default_commission_rate' => (float) config('services.goals.commission_rate', 0.05),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'target_date' => 'nullable|date|after:today',
        ]);
        $user = $request->user();

        $goal = SavingsGoal::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'target_amount' => round((float)$validated['target_amount'], 2),
            'target_date' => $validated['target_date'] ?? null,
            'saved_amount' => 0,
            'status' => 'active',
        ]);

        return response()->json($goal, 201);
    }

    public function deposit(Request $request, int $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);
        $user = $request->user();
        $goal = SavingsGoal::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        if ($goal->status !== 'active') {
            return response()->json(['message' => 'Goal is not active'], 422);
        }
        $amount = round((float)$validated['amount'], 2);
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid amount'], 422);
        }
        if ((float)$user->balance < $amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        $reference = 'GOAL_DEPOSIT_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        DB::transaction(function () use ($user, $goal, $amount, $reference) {
            // Deduct from wallet
            $user->decrement('balance', $amount);

            // Credit to goal
            $goal->increment('saved_amount', $amount);

            // Auto-complete if target reached
            $goal->refresh();
            if ((float)$goal->saved_amount >= (float)$goal->target_amount && $goal->status === 'active') {
                $goal->update(['status' => 'completed']);
            }

            // Record wallet transaction (debit)
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'source' => 'goal_deposit',
                'meta' => [
                    'goal_id' => $goal->id,
                    'goal_title' => $goal->title,
                ],
            ]);
        });

        $user->refresh();
        $goal->refresh();

        return response()->json([
            'message' => 'Deposit successful',
            'reference' => $reference,
            'balance' => (float)$user->balance,
            'goal' => $goal,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $goal = SavingsGoal::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $target = (float) $goal->target_amount;
        $saved = (float) $goal->saved_amount;
        $progress = $target > 0 ? round(min(100, ($saved / $target) * 100), 2) : 0;
        $goalArr = array_merge($goal->toArray(), [
            'progress' => $progress,
            'is_complete' => $saved >= $target,
        ]);
        $bookings = $goal->bookings()->latest()->get();
        return response()->json([
            'goal' => $goalArr,
            'bookings' => $bookings,
        ]);
    }

    public function book(Request $request, int $id)
    {
        $validated = $request->validate([
            'partner_name' => 'required|string|max:120',
            'package' => 'nullable|string|max:120',
            'booking_amount' => 'nullable|numeric|min:1',
        ]);
        $user = $request->user();
        $goal = SavingsGoal::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // Only allow booking if goal is completed (target reached) and not already booked
        if (!in_array($goal->status, ['completed', 'active'])) {
            return response()->json(['message' => 'Goal cannot be booked'], 422);
        }
        // Allow booking if saved >= target (treat as completed); auto-mark completed if not yet
        if ((float)$goal->saved_amount < (float)$goal->target_amount) {
            return response()->json(['message' => 'Goal target not yet reached'], 422);
        }

        $amount = isset($validated['booking_amount']) ? round((float)$validated['booking_amount'], 2) : (float)$goal->saved_amount;
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid booking amount'], 422);
        }
        if ($amount > (float)$goal->saved_amount) {
            return response()->json(['message' => 'Booking amount exceeds saved amount'], 422);
        }

        $rate = (float) config('services.goals.commission_rate', 0.05);
        $commission = round($amount * max(0, min(1, $rate)), 2);
        $reference = 'GOAL_BOOK_' . now()->format('YmdHis') . '_' . $user->id . '_' . bin2hex(random_bytes(3));

        $booking = DB::transaction(function () use ($user, $goal, $validated, $amount, $rate, $commission, $reference) {
            // Record booking; do not move funds back to wallet; they remain locked under the goal ledger
            $b = GoalBooking::create([
                'user_id' => $user->id,
                'savings_goal_id' => $goal->id,
                'partner_name' => $validated['partner_name'],
                'package' => $validated['package'] ?? null,
                'booking_amount' => $amount,
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'reference' => $reference,
                'status' => 'booked',
            ]);

            // Mark goal as booked (still locked)
            if ($goal->status !== 'booked') {
                $goal->update(['status' => 'booked']);
            }

            // Notify relevant admins about the new booking
            $user->getAuthorizedAdmins()->each(function ($admin) use ($user, $b, $goal) {
                $admin->notifyMember(
                    "New Goal Booking",
                    "Member {$user->name} booked their '{$goal->title}' goal with {$b->partner_name}.",
                    ['type' => 'goal_booking', 'booking_id' => $b->id]
                );
            });

            return $b;
        });

        return response()->json([
            'message' => 'Booking recorded. Our partner will contact you to finalize your trip.',
            'booking' => $booking,
            'commission_amount' => $commission,
            'commission_rate' => $rate,
            'reference' => $reference,
        ], 201);
    }
}
