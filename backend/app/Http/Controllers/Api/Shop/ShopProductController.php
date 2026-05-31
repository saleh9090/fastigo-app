<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\Request;

class ShopProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        $products = Product::query()
            ->with('category')
            ->where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'category_name' => $product->category?->name,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'active' => $product->active,
            ]);

        return response()->json([
            'products' => $products,
        ]);
    }

    public function categories(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        $categories = ProductCategory::query()
            ->where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'active' => $category->active,
            ]);

        return response()->json([
            'product_categories' => $categories,
        ]);
    }

    private function shopUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function forbiddenResponse()
    {
        return response()->json([
            'message' => 'Authenticated user is not allowed to access shop products.',
        ], 403);
    }
}
