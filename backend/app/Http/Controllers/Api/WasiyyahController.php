<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WasiyyahController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'beneficiaries' => $request->user()->beneficiaries
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
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

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
            'percentage' => 'sometimes|required|numeric|min:0|max:100',
        ]);

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
