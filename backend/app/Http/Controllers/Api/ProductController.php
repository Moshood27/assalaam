<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
    * List active products for the member storefront.
    * Supports pagination, optional search by name, and simple sorting.
    */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 12);
        $perPage = max(1, min(100, $perPage));
        $search = trim((string) $request->get('q', ''));
        $sort = trim((string) $request->get('sort', 'newest'));

        $query = Product::query()->where('is_active', true);
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting options: newest (default), price_asc, price_desc, name_asc, name_desc
        switch ($sort) {
            case 'price_asc':
                // Order by computed price using cost_price + markup_percent approximation
                $query->orderByRaw('(cost_price + (cost_price * (markup_percent / 100))) asc');
                break;
            case 'price_desc':
                $query->orderByRaw('(cost_price + (cost_price * (markup_percent / 100))) desc');
                break;
            case 'name_asc':
                $query->orderBy('name');
                break;
            case 'name_desc':
                $query->orderByDesc('name');
                break;
            case 'newest':
            default:
                $query->orderByDesc('created_at');
                break;
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
                'created_at' => optional($p->created_at)->toIso8601String(),
            ];
        });

        return response()->json($paginator);
    }
}
