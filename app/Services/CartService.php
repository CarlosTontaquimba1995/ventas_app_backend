<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Interfaces\CartItemRepositoryInterface;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\Interfaces\CartServiceInterface;
use Illuminate\Support\Facades\App;

class CartService implements CartServiceInterface
{
    protected $cartRepository;
    protected $cartItemRepository;
    protected $productRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartItemRepositoryInterface $cartItemRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->productRepository = $productRepository;
    }

    public function addItemToCart(int $cartId, int $productId, int $quantity = 1)
    {
        $product = $this->productRepository->findActiveById($productId);
        
        if (!$product) {
            throw new \Exception('Product not found or inactive');
        }
        
        if ($product->stock_quantity < $quantity) {
            throw new \Exception('Insufficient stock available');
        }

        // Check if item already exists in cart
        $existingItem = $this->cartItemRepository->getCartItem($cartId, $productId);

        if ($existingItem) {
            // Update quantity if item exists
            $newQuantity = $existingItem->quantity + $quantity;
            $this->cartItemRepository->update($existingItem->id, [
                'quantity' => $newQuantity
            ]);
            return $this->cartItemRepository->findById($existingItem->id);
        }

        // Create new cart item
        return $this->cartItemRepository->create([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $product->final_price,
        ]);
    }

    public function getCartTotal(int $cartId): float
    {
        return $this->cartItemRepository->getCartTotal($cartId);
    }

    public function getCartItems(int $cartId)
    {
        return $this->cartItemRepository->getByCartId($cartId);
    }

    public function updateCartItemQuantity(int $cartItemId, int $quantity): bool
    {
        return $this->cartItemRepository->updateQuantity($cartItemId, $quantity);
    }

    public function removeItemFromCart(int $cartItemId): bool
    {
        return $this->cartItemRepository->deleteById($cartItemId);
    }

    public function clearCart(int $cartId): bool
    {
        return $this->cartItemRepository->clearCart($cartId);
    }
}
