<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemRequest;
use App\Http\Requests\CartRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Services\Interfaces\CartServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;
    protected $user;

    public function __construct(CartServiceInterface $cartService)
    {
        $this->cartService = $cartService;
        $this->middleware('auth:api');
        $this->user = auth('api')->user();
    }

    public function addItem(CartItemRequest $request, $productId)
    {
        try {
            $validated = $request->validated();
            $item = $this->cartService->addItemToCart(
                $this->user->cart->id, 
                $productId, 
                $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart',
                'data' => $item,
                'cart_total' => $this->cartService->getCartTotal($this->user->cart->id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function updateItem(CartItemRequest $request, $itemId)
    {
        try {
            $validated = $request->validated();
            $success = $this->cartService->updateCartItemQuantity($itemId, $validated['quantity']);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update item'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart updated',
                'data' => [
                    'cart_total' => $this->cartService->getCartTotal($this->user->cart->id)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function removeItem($itemId)
    {
        try {
            $success = $this->cartService->removeItemFromCart($itemId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove item'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart',
                'data' => [
                    'cart_total' => $this->cartService->getCartTotal($this->user->cart->id)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getCart()
    {
        try {
            $cart = $this->user->cart;
            
            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => [],
                        'total' => 0,
                        'total_items' => 0
                    ]
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $this->cartService->getCartItems($cart->id),
                    'total' => $this->cartService->getCartTotal($cart->id),
                    'total_items' => $cart->items->sum('quantity')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function clearCart()
    {
        try {
            $this->cartService->clearCart($this->user->cart->id);

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared',
                'data' => [
                    'cart_total' => 0,
                    'total_items' => 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
