<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\Interfaces\CategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected CategoryServiceInterface $categoryService;

    public function __construct(CategoryServiceInterface $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the categories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $parentId = $request->query('parent_id', null);
        $includeChildren = $request->boolean('include_children', false);
        
        if ($parentId === null) {
            // Get root categories (categories without parent)
            $categories = $this->categoryService->getRootCategories();
        } else {
            // Get categories by parent_id
            $categories = $this->categoryService->getCategoriesByParent($parentId);
        }
        
        $response = [
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ];
        
        if ($includeChildren && $parentId !== null) {
            $response['children'] = CategoryResource::collection(
                $this->categoryService->getCategoryWithChildren($parentId)
            );
        }
        
        return response()->json($response);
    }
    
    /**
     * Get a category with its children hierarchy.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showWithChildren($id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryWithChildren($id);
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);
        }
    }

    /**
     * Store a newly created category in storage.
     * 
     * Example request body for creating a category with children:
     * {
     *     "name": "Beverages",
     *     "description": "All types of drinks",
     *     "is_active": true,
     *     "order": 1,
     *     "children": [
     *         {
     *             "name": "Alcoholic",
     *             "description": "Alcoholic beverages",
     *             "is_active": true,
     *             "order": 1,
     *             "children": [
     *                 {
     *                     "name": "Beer",
     *                     "is_active": true,
     *                     "order": 1
     *                 },
     *                 {
     *                     "name": "Wine",
     *                     "is_active": true,
     *                     "order": 2
     *                 }
     *             ]
     *         },
     *         {
     *             "name": "Non-Alcoholic",
     *             "description": "Non-alcoholic beverages",
     *             "is_active": true,
     *             "order": 2,
     *             "children": [
     *                 {
     *                     "name": "Water",
     *                     "is_active": true,
     *                     "order": 1
     *                 },
     *                 {
     *                     "name": "Soda",
     *                     "is_active": true,
     *                     "order": 2
     *                 }
     *             ]
     *         }
     *     ]
     * }
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Check if we have children to create
            $hasChildren = !empty($validated['children']);
            
            // Create the category with its children if they exist
            $category = $this->categoryService->createCategory($validated, $hasChildren);
            
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category->load('children')),
                'message' => 'Category ' . ($hasChildren ? 'and its subcategories ' : '') . 'created successfully.'
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category' . (isset($hasChildren) && $hasChildren ? ' and/or its subcategories' : ''),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = $this->categoryService->updateCategory($id, $request->all());
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category),
                'message' => 'Category updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->categoryService->deleteCategory($id);
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Category deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category by slug
     */
    public function getBySlug(string $slug): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryBySlug($slug);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
