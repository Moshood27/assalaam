<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function __construct()
    {
        // Ensure only admins can access these endpoints
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!$user || !(bool) $user->is_admin) {
                return response()->json(['message' => 'Admins only'], 403);
            }
            return $next($request);
        });
    }

    /**
     * List products for admin management (basic fields only)
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $query = Product::query()->orderByDesc('created_at');
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }
        $perPage = (int) ($request->integer('per_page') ?: 20);
        $perPage = max(1, min(100, $perPage));
        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(function (Product $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'image_url' => $p->image_url,
                'cost_price' => (float) $p->cost_price,
                'markup_percent' => (float) $p->markup_percent,
                'is_active' => (bool) $p->is_active,
                'created_at' => $p->created_at,
            ];
        });
        return response()->json($paginator);
    }

    /**
     * Upload/replace a product image (max 1000KB).
     */
    public function uploadImage(Request $request, $id)
    {
        $validated = $request->validate([
            'image' => 'required|image|max:1000', // size in KB
        ]);

        $product = Product::findOrFail($id);

        // Delete existing local image if present
        $this->deleteExistingIfLocal($product);

        $file = $request->file('image');
        $path = $file->store('products', 'public');
        $url = Storage::disk('public')->url($path);

        $product->image_url = $url;
        $product->save();

        return response()->json([
            'message' => 'Image uploaded',
            'product' => $product,
        ]);
    }

    /**
     * Remove a product image.
     */
    public function deleteImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->deleteExistingIfLocal($product);
        $product->image_url = null;
        $product->save();

        return response()->json([
            'message' => 'Image removed',
            'product' => $product,
        ]);
    }

    private function deleteExistingIfLocal(Product $product): void
    {
        $url = (string) ($product->image_url ?? '');
        // If image_url points to our public storage (typically /storage/...), delete it
        if ($url !== '') {
            $parsed = parse_url($url, PHP_URL_PATH);
            if (is_string($parsed) && str_starts_with($parsed, '/storage/')) {
                $relative = ltrim(substr($parsed, strlen('/storage/')), '/');
                if ($relative !== '' && Storage::disk('public')->exists($relative)) {
                    try { Storage::disk('public')->delete($relative); } catch (\Throwable $e) { /* ignore */ }
                }
            }
        }
    }
}
