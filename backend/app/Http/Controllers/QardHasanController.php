<?php

namespace App\Http\Controllers;

use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ShariahAuditLog as ShariahAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QardHasanController extends Controller
{
    public function index(Request $request)
    {
        // In production, would use $request->user(); for now, list all for demo
        $items = QardHasan::with('repayments')->latest()->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'total_installments' => ['required', 'integer', 'min:1'],
            'interval' => ['nullable', 'string'],
            'admin_fee_flat' => ['nullable', 'numeric', 'min:0'],
            'admin_fee_pct' => ['nullable', 'numeric', 'min:0', 'max:2'], // cap to 2% by policy
        ]);

        $perInstallment = round($data['principal_amount'] / $data['total_installments'], 2);

        $q = QardHasan::create([
            'user_id' => $data['user_id'],
            'qard_id_string' => 'QH-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
            'principal_amount' => $data['principal_amount'],
            'total_installments' => $data['total_installments'],
            'per_installment' => $perInstallment,
            'interval' => $data['interval'] ?? 'Monthly',
            'admin_fee_flat' => $data['admin_fee_flat'] ?? 0,
            'admin_fee_pct' => $data['admin_fee_pct'] ?? 0,
            'paid_amount' => 0,
            'status' => 'active',
        ]);

        ShariahAudit::log(null, 'create_qard_hasan', $q->toArray());

        return response()->json($q, 201);
    }

    public function repay(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data, $id) {
            $q = QardHasan::lockForUpdate()->findOrFail($id);

            $rep = QardHasanRepayment::create([
                'qard_hasan_id' => $q->id,
                'amount' => $data['amount'],
                'reference' => 'REF-'.Str::upper(Str::random(10)),
                'status' => 'success', // prototype: mark success immediately
                'paid_at' => now(),
            ]);

            $q->paid_amount = (float)$q->paid_amount + (float)$data['amount'];
            if ($q->paid_amount >= $q->principal_amount) {
                $q->status = 'completed';
            }
            $q->save();

            ShariahAudit::log(null, 'repay_qard_hasan', [
                'qard' => $q->qard_id_string,
                'amount' => $data['amount'],
                'reference' => $rep->reference,
            ]);

            return response()->json([
                'qard' => $q,
                'repayment' => $rep,
            ]);
        });
    }
}
