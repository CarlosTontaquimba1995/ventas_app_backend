<?php

namespace App\Services\Interfaces;

use App\Models\Product;

interface CartServiceInterface
{
    /**
     * Add an item to the cart
     */
    public function addItemToCart(int $cartId, Product $product, int $quantity = 1);

    /**
     * Get cart total
     */
    public function getCartTotal(int $cartId): float;

    /**
     * Get cart items
     */
    public function getCartItems(int $cartId);

    /**
     * Update cart item quantity
     */
    public function updateCartItemQuantity(int $cartItemId, int $quantity): bool;

    /**
     * Remove item from cart
     */
    public function removeItemFromCart(int $cartItemId): bool;

    /**
     * Clear cart
     */
    public function clearCart(int $cartId): bool;
}
