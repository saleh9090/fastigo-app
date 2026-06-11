<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        $products = Product::query()
            ->with(['category', 'unit'])
            ->where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'unit_id' => $product->unit_id,
                'unit_name' => $product->unit?->name,
                'name' => $product->name,
                'type' => $product->type,
                'description' => $product->description,
                'price' => $product->price,
                'active' => $product->active,
            ]);

        return response()->json([
            'items' => $products,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user)) {
            return $this->forbiddenResponse();
        }

        $validated = $this->validateProduct($request, $user);

        $product = Product::create([
            ...$validated,
            'company_id' => $user->company_id,
        ]);

        $product->load(['category', 'unit']);

        return response()->json([
            'item' => $this->formatProduct($product),
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user) || ! $this->belongsToCompany($product, $user)) {
            return $this->forbiddenResponse();
        }

        $product->update($this->validateProduct($request, $user));
        $product->load(['category', 'unit']);

        return response()->json([
            'item' => $this->formatProduct($product),
        ]);
    }

    public function destroy(Request $request, Product $product)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user) || ! $this->belongsToCompany($product, $user)) {
            return $this->forbiddenResponse();
        }

        $product->delete();

        return response()->json([
            'message' => 'Item deleted.',
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
            'categories' => $categories,
            'product_categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user)) {
            return $this->forbiddenResponse();
        }

        $category = ProductCategory::create([
            ...$this->validateCategory($request),
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'category' => $this->formatCategory($category),
        ], 201);
    }

    public function updateCategory(Request $request, ProductCategory $category)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user) || ! $this->belongsToCompany($category, $user)) {
            return $this->forbiddenResponse();
        }

        $category->update($this->validateCategory($request));

        return response()->json([
            'category' => $this->formatCategory($category),
        ]);
    }

    public function destroyCategory(Request $request, ProductCategory $category)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user) || ! $this->belongsToCompany($category, $user)) {
            return $this->forbiddenResponse();
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted.',
        ]);
    }

    public function units(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        $units = Unit::query()
            ->where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Unit $unit): array => $this->formatUnit($unit));

        return response()->json([
            'units' => $units,
        ]);
    }

    public function storeUnit(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user)) {
            return $this->forbiddenResponse();
        }

        $unit = Unit::create([
            ...$this->validateUnit($request),
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'unit' => $this->formatUnit($unit),
        ], 201);
    }

    public function updateUnit(Request $request, Unit $unit)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user) || ! $this->belongsToCompany($unit, $user)) {
            return $this->forbiddenResponse();
        }

        $unit->update($this->validateUnit($request));

        return response()->json([
            'unit' => $this->formatUnit($unit),
        ]);
    }

    public function destroyUnit(Request $request, Unit $unit)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageItems($user) || ! $this->belongsToCompany($unit, $user)) {
            return $this->forbiddenResponse();
        }

        $unit->delete();

        return response()->json([
            'message' => 'Unit deleted.',
        ]);
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'unit_id' => $product->unit_id,
            'unit_name' => $product->unit?->name,
            'name' => $product->name,
            'type' => $product->type,
            'description' => $product->description,
            'price' => $product->price,
            'active' => $product->active,
        ];
    }

    private function formatCategory(ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'active' => $category->active,
        ];
    }

    private function formatUnit(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'description' => $unit->description,
            'active' => $unit->active,
        ];
    }

    private function validateProduct(Request $request, User $user): array
    {
        return $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('company_id', $user->company_id),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                Rule::exists('units', 'id')->where('company_id', $user->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['service', 'product'])],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    private function validateUnit(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    private function shopUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function canManageItems(?User $user): bool
    {
        return $user instanceof User
            && (bool) $user->company_id
            && $user->role === 'company_manager';
    }

    private function belongsToCompany(Product|ProductCategory|Unit $record, User $user): bool
    {
        return (int) $record->company_id === (int) $user->company_id;
    }

    private function forbiddenResponse()
    {
        return response()->json([
            'message' => 'Authenticated user is not allowed to access shop products.',
        ], 403);
    }
}
