<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
    * List active products for the member storefront.
    * Supports simple pagination and optional search by name.
    */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 12);
        $perPage = max(1, min(100, $perPage));
        $search = trim((string) $request->get('q', ''));

        $query = Product::query()->where('is_active', true)->orderByDesc('created_at');
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        // Product model appends selling_price; hide raw cost for members
        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(function (Product $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'image_url' => $p->image_url,
                'selling_price' => $p->selling_price,
            ];
        });

        return response()->json($paginator);
    }
}
