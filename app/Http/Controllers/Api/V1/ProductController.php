<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Requests\BulkCreateProductRequest;
use App\Models\Product;
use App\Services\Interfaces\ProductServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\URL;

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
    /**
     * Display a paginated listing of products with the exact required format.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        // Get paginated products with category relationship
        $products = $this->productService->getPaginatedProductsWithCategory($perPage, $page);
        
        // Format the response to match the required structure
        $response = [
            'current_page' => $products->currentPage(),
            'data' => ProductResource::collection($products->items()),
            'first_page_url' => $products->url(1),
            'from' => $products->firstItem(),
            'last_page' => $products->lastPage(),
            'last_page_url' => $products->url($products->lastPage()),
            'links' => $this->formatPaginationLinks($products),
            'next_page_url' => $products->nextPageUrl(),
            'path' => URL::current(),
            'per_page' => $products->perPage(),
            'prev_page_url' => $products->previousPageUrl(),
            'to' => $products->lastItem(),
            'total' => $products->total()
        ];

        return response()->json($response);
    }
    
    /**
     * Get products by category ID with pagination
     *
     * @param int $categoryId
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductsByCategory(int $categoryId, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        // Get paginated products by category
        $products = $this->productService->getPaginatedProductsByCategory($categoryId, $perPage, $page);
        
        // Format the response to match the required structure
        $response = [
            'current_page' => $products->currentPage(),
            'data' => ProductResource::collection($products->items()),
            'first_page_url' => $products->url(1),
            'from' => $products->firstItem(),
            'last_page' => $products->lastPage(),
            'last_page_url' => $products->url($products->lastPage()),
            'links' => $this->formatPaginationLinks($products),
            'next_page_url' => $products->nextPageUrl(),
            'path' => URL::current(),
            'per_page' => $products->perPage(),
            'prev_page_url' => $products->previousPageUrl(),
            'to' => $products->lastItem(),
            'total' => $products->total()
        ];

        return response()->json($response);
    }
    
    /**
     * Format pagination links to match the required structure.
     */
    private function formatPaginationLinks($paginator): array
    {
        $links = [];
        
        // Previous page link
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false
        ];
        
        // Page number links
        foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url) {
            $links[] = [
                'url' => $url,
                'label' => (string)$page,
                'active' => $page == $paginator->currentPage()
            ];
        }
        
        // Next page link
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false
        ];
        
        return $links;
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
    public function show($id): JsonResponse
    {
        $product = $this->productService->findActiveById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(new ProductResource($product));
    }

    /**
     * Store multiple products in storage.
     *
     * @param  \App\Http\Requests\BulkCreateProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStore(BulkCreateProductRequest $request): JsonResponse
    {
        try {
            $products = $this->productService->bulkCreate($request->products);
            
            return response()->json([
                'message' => 'Products created successfully',
                'data' => ProductResource::collection($products),
                'count' => count($products)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create products',
                'error' => $e->getMessage()
            ], 500);
        }
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
