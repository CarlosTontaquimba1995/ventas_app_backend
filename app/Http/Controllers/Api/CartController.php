<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function addItem(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($productId);
        $item = $this->cartService->addItemToCart($this->user->cart->id, $product, $request->quantity);

        return response()->json([
            'message' => 'Item added to cart',
            'item' => $item,
            'cart_total' => $this->cartService->getCartTotal($this->user->cart->id)
        ]);
    }

    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $success = $this->cartService->updateCartItemQuantity($itemId, $request->quantity);

        if (!$success) {
            return response()->json(['message' => 'Failed to update item'], 400);
        }

        return response()->json([
            'message' => 'Cart updated',
            'cart_total' => $this->cartService->getCartTotal($this->user->cart->id)
        ]);
    }

    public function removeItem($itemId)
    {
        $success = $this->cartService->removeItemFromCart($itemId);

        if (!$success) {
            return response()->json(['message' => 'Failed to remove item'], 400);
        }

        return response()->json([
            'message' => 'Item removed from cart',
            'cart_total' => $this->cartService->getCartTotal($this->user->cart->id)
        ]);
    }

    public function getCart()
    {
        $cart = $this->user->cart;
        
        return response()->json([
            'items' => $this->cartService->getCartItems($cart->id),
            'total' => $this->cartService->getCartTotal($cart->id),
            'total_items' => $cart->items->sum('quantity')
        ]);
    }

    public function clearCart()
    {
        $this->cartService->clearCart($this->user->cart->id);

        return response()->json([
            'message' => 'Cart cleared',
            'cart_total' => 0,
            'total_items' => 0
        ]);
    }
}
