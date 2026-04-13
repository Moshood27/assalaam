<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WasiyyahController extends Controller
{
    public function index(Request $request)
    {
        $beneficiaries = $request->user()->beneficiaries;
        $summary = $beneficiaries->groupBy('asset_type')->map(function ($group) {
            return (float) $group->sum('percentage');
        });

        return response()->json([
            'beneficiaries' => $beneficiaries,
            'summary' => $summary
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'percentage' => 'required|numeric|min:0.01|max:100',
            'asset_type' => 'nullable|string|in:all,shares,savings,takaful,passbook',
        ]);

        $assetType = $validated['asset_type'] ?? 'all';
        $validated['asset_type'] = $assetType;

        $currentTotal = $request->user()->beneficiaries()
            ->where('asset_type', $assetType)
            ->sum('percentage');

        if ($currentTotal + $validated['percentage'] > 100) {
            return response()->json([
                'message' => "Total percentage for '{$assetType}' allocation cannot exceed 100%. Currently at {$currentTotal}%."
            ], 422);
        }

        $beneficiary = $request->user()->beneficiaries()->create($validated);

        return response()->json([
            'message' => 'Beneficiary added successfully',
            'beneficiary' => $beneficiary
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $beneficiary = $request->user()->beneficiaries()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'relationship' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'percentage' => 'sometimes|required|numeric|min:0.01|max:100',
            'asset_type' => 'nullable|string|in:all,shares,savings,takaful,passbook',
        ]);

        if (isset($validated['percentage']) || isset($validated['asset_type'])) {
            $newAssetType = $validated['asset_type'] ?? $beneficiary->asset_type;
            $newPercentage = $validated['percentage'] ?? $beneficiary->percentage;

            $otherTotal = $request->user()->beneficiaries()
                ->where('id', '!=', $id)
                ->where('asset_type', $newAssetType)
                ->sum('percentage');

            if ($otherTotal + $newPercentage > 100) {
                return response()->json([
                    'message' => "Total percentage for '{$newAssetType}' allocation cannot exceed 100%. Currently at {$otherTotal}%."
                ], 422);
            }
        }

        $beneficiary->update($validated);

        return response()->json([
            'message' => 'Beneficiary updated successfully',
            'beneficiary' => $beneficiary
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $beneficiary = $request->user()->beneficiaries()->findOrFail($id);
        $beneficiary->delete();

        return response()->json([
            'message' => 'Beneficiary deleted successfully'
        ]);
    }
}
