<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ShariahAuditLog as ShariahAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVendorController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!$user || !(bool) $user->is_admin) {
                return response()->json(['message' => 'Admins only'], 403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $query = Vendor::query()->with('owner:id,name,phone,email')->latest();
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        return response()->json($query->paginate(20));
    }

    public function approve(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->is_approved = true;
        $vendor->approved_at = now();
        $vendor->approved_by_id = $request->user()->id;
        $vendor->save();

        if ($vendor->owner) {
            $vendor->owner->notifyMember(
                'Vendor Profile Approved',
                "Your business profile '{$vendor->name}' has been approved. You can now start adding products to the store.",
                ['vendor_id' => $vendor->id, 'type' => 'vendor_approved']
            );
        }

        return response()->json(['message' => 'Vendor approved', 'vendor' => $vendor]);
    }

    public function reject(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->is_approved = false;
        $vendor->save();

        return response()->json(['message' => 'Vendor marked as pending', 'vendor' => $vendor]);
    }

    public function toggleActive(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->is_active = !$vendor->is_active;
        $vendor->save();

        return response()->json([
            'message' => $vendor->is_active ? 'Vendor activated' : 'Vendor deactivated',
            'vendor' => $vendor
        ]);
    }

    public function settlements(Request $request)
    {
        $query = WithdrawalRequest::where('meta->is_vendor_settlement', true)
            ->with('user:id,name,phone')
            ->latest();

        return response()->json($query->paginate(20));
    }

    public function approveSettlement(Request $request, $id)
    {
        $record = WithdrawalRequest::where('id', $id)
            ->where('meta->is_vendor_settlement', true)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            DB::transaction(function () use ($record) {
                $user = User::where('id', $record->user_id)->lockForUpdate()->first();
                if ((float)$user->balance < (float)$record->amount) {
                    throw new \RuntimeException('Insufficient member wallet balance.');
                }
                $user->decrement('balance', (float)$record->amount);

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'debit',
                    'amount' => (float)$record->amount,
                    'reference' => $record->reference,
                    'source' => 'bank_withdrawal',
                    'meta' => [
                        'withdrawal_request_id' => $record->id,
                        'is_vendor_settlement' => true,
                        'bank_code' => $record->bank_code,
                        'bank_name' => $record->bank_name,
                        'account_number' => $record->account_number,
                        'account_name' => $record->account_name,
                    ],
                ]);

                $record->status = 'paid';
                $record->processed_at = now();
                $record->save();

                if (class_exists(ShariahAudit::class)) {
                    ShariahAudit::log(auth()->user(), 'approve_vendor_settlement', [
                        'withdrawal_request_id' => $record->id,
                        'user_id' => $record->user_id,
                        'amount' => $record->amount,
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $user = $record->user?->fresh();
        if ($user) {
            $msg = 'Vendor Settlement paid: ₦'.number_format((float)$record->amount, 2).' to '.$record->bank_name.'. Ref: '.$record->reference;
            $user->notifyMember('Settlement Paid', $msg, [
                'type' => 'vendor_settlement_paid',
                'amount' => (float)$record->amount,
                'reference' => $record->reference,
            ]);
        }

        return response()->json(['message' => 'Settlement marked as paid']);
    }

    public function rejectSettlement(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        $record = WithdrawalRequest::where('id', $id)
            ->where('meta->is_vendor_settlement', true)
            ->where('status', 'pending')
            ->firstOrFail();

        $record->status = 'declined';
        $record->reason = $request->reason;
        $record->processed_at = now();
        $record->save();

        $user = $record->user?->fresh();
        if ($user) {
            $msg = 'Vendor Settlement declined: ₦'.number_format((float)$record->amount, 2).'. Reason: '.$request->reason;
            $user->notifyMember('Settlement Declined', $msg, [
                'type' => 'vendor_settlement_declined',
                'reason' => $request->reason,
            ]);
        }

        return response()->json(['message' => 'Settlement declined']);
    }
}
