<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemRequest;
use App\Services\Interfaces\CartItemServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    public function __construct(
        private CartItemServiceInterface $cartItemService
    ) {}

    /**
     * Display a listing of cart items.
     */
    /**
     * Display a listing of cart items.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        // Return empty array if no cart exists for the user
        if (!$user->cart) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
        
        // Get cart items using the service
        $items = $this->cartItemService->getCartItems($user->cart->id);
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Add a new item to the cart.
     */
    /**
     * Add a new item to the cart.
     *
     * @param CartItemRequest $request
     * @return JsonResponse
     */
    public function store(CartItemRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get or create cart for the user
            $cart = $user->cart ?? $this->cartItemService->createCart($user->id);
            $cartId = $cart->id;
            
            $validated = $request->validated();
            
            $item = $this->cartItemService->addItemToCart(
                $cartId,
                $validated['product_id'],
                $validated['quantity'] ?? 1,
                $validated['price']
            );

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add item to cart. The product may not exist or is not available.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the item to the cart.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified cart item.
     */
    public function update(CartItemRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $item = $this->cartItemService->updateItemQuantity(
            $id,
            $validated['quantity']
        );

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found or update failed'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Remove the specified cart item.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->cartItemService->removeItemFromCart($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found or already deleted'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    /**
     * Get cart summary.
     */
    public function getCartSummary(): JsonResponse
    {
        $cartId = Auth::user()->cart->id;
        
        return response()->json([
            'success' => true,
            'data' => [
                'items_count' => $this->cartItemService->getCartItemsCount($cartId),
                'total' => $this->cartItemService->getCartTotal($cartId)
            ]
        ]);
    }

    /**
     * Bulk update cart items.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:cart_items,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        $success = $this->cartItemService->bulkUpdateCartItems(
            Auth::user()->cart->id,
            $request->input('items')
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart items'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully'
        ]);
    }
}
