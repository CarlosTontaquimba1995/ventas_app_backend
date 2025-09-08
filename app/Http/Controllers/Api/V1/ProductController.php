<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Interfaces\ProductServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductServiceInterface $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $products = $this->productService->getProductsByStatus(true, $perPage);
        return response()->json($products);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|unique:products,sku',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = $this->productService->createProduct($request->all());
        return response()->json($product, 201);
    }

    /**
     * Display the specified product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'category_id' => 'exists:categories,id',
            'stock' => 'integer|min:0',
            'sku' => [
                'string',
                Rule::unique('products')->ignore($id),
            ],
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = $this->productService->updateProduct($id, $request->all());
        
        if (!$updated) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['message' => 'Product updated successfully']);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->productService->deleteProduct($id);
        
        if (!$deleted) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * Get products by category ID.
     */
    public function getByCategory(int $categoryId, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $products = $this->productService->getByCategory($categoryId, $perPage);
        return response()->json($products);
    }

    /**
     * Get featured products.
     */
    public function featured(int $limit = 5): JsonResponse
    {
        $products = $this->productService->getFeatured($limit);
        return response()->json($products);
    }

    /**
     * Get new arrival products.
     */
    public function newArrivals(int $limit = 5): JsonResponse
    {
        $products = $this->productService->getNewArrivals($limit);
        return response()->json($products);
    }

    /**
     * Search products by query.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');
        $perPage = $request->input('per_page', 10);
        
        if (empty($query)) {
            return response()->json(['message' => 'Search query is required'], 400);
        }
        
        $results = $this->productService->search($query, $perPage);
        return response()->json($results);
    }
}
