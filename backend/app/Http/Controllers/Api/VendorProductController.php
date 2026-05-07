<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorProductController extends Controller
{
    protected function requireVendor(Request $request): Vendor
    {
        $user = $request->user();
        $vendor = Vendor::where('owner_user_id', $user->id)->first();
        if (!$vendor) {
            abort(403, 'Create a vendor profile first.');
        }
        if (!$vendor->is_active || !$vendor->is_approved) {
            abort(403, 'Vendor not active/approved yet.');
        }
        return $vendor;
    }

    public function index(Request $request)
    {
        $vendor = $this->requireVendor($request);
        $per = (int) ($request->integer('per_page') ?: 20);
        $per = max(1, min(100, $per));
        $q = Product::query()->where('vendor_id', $vendor->id)->latest();
        return response()->json($q->paginate($per));
    }

    public function store(Request $request)
    {
        $vendor = $this->requireVendor($request);

        // Transform boolean-ish strings to real booleans before validation
        if ($request->has('track_stock')) {
            $request->merge(['track_stock' => $request->boolean('track_stock')]);
        }
        if ($request->has('is_active')) {
            $request->merge(['is_active' => $request->boolean('is_active')]);
        }

        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'cost_price' => 'required|numeric|min:0',
            'markup_percent' => 'required|numeric|min:0|max:1000',
            'stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'nullable|boolean',
            'image_url' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:10240', // 10MB
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('image_url')) {
            $val = (string) $request->input('image_url');
            $storageUrl = config('app.url') . '/storage/';
            if (str_starts_with($val, $storageUrl)) {
                $validated['image_url'] = substr($val, strlen($storageUrl));
            } elseif (str_starts_with($val, '/storage/')) {
                $validated['image_url'] = substr($val, strlen('/storage/'));
            }
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $p = new Product();
        $p->fill($validated);
        $p->vendor_id = $vendor->id;
        $p->is_approved = false; // Always false for new vendor products
        $p->is_active = (bool)($validated['is_active'] ?? false);
        $p->stock_quantity = (int)($validated['stock_quantity'] ?? 0);
        $p->track_stock = (bool)($validated['track_stock'] ?? false);
        $p->save();

        return response()->json(['message' => 'Product created', 'product' => $p], 201);
    }

    public function update(Request $request, $id)
    {
        $vendor = $this->requireVendor($request);
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);

        // Transform boolean-ish strings to real booleans before validation
        if ($request->has('track_stock')) {
            $request->merge(['track_stock' => $request->boolean('track_stock')]);
        }
        if ($request->has('is_active')) {
            $request->merge(['is_active' => $request->boolean('is_active')]);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|integer|exists:categories,id',
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:2000',
            'cost_price' => 'sometimes|numeric|min:0',
            'markup_percent' => 'sometimes|numeric|min:0|max:1000',
            'stock_quantity' => 'sometimes|integer|min:0',
            'track_stock' => 'sometimes|boolean',
            'image_url' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:10240', // 10MB
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->has('image_url')) {
            $val = (string) $request->input('image_url');
            $storageUrl = config('app.url') . '/storage/';
            if (str_starts_with($val, $storageUrl)) {
                $validated['image_url'] = substr($val, strlen($storageUrl));
            } elseif (str_starts_with($val, '/storage/')) {
                $validated['image_url'] = substr($val, strlen('/storage/'));
            }
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $product->fill($validated);
        // If critical fields are changed, require re-approval
        if ($request->hasAny(['name', 'cost_price', 'markup_percent'])) {
            $product->is_approved = false;
        }
        $product->save();
        return response()->json(['message' => 'Product updated', 'product' => $product]);
    }

    public function destroy(Request $request, $id)
    {
        $vendor = $this->requireVendor($request);
        $product = Product::where('vendor_id', $vendor->id)->findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
