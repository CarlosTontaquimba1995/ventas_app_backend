<?php

namespace App\Services\Interfaces;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartItemServiceInterface
{
    public function createCart(int $userId): Cart;
    
    public function getCartItems(int $cartId): Collection;
    
    public function addItemToCart(int $cartId, int $productId, int $quantity, float $price): ?CartItem;
    
    public function updateItemQuantity(int $cartItemId, int $quantity): ?CartItem;
    
    public function removeItemFromCart(int $cartItemId): bool;
    
    public function getCartTotal(int $cartId): float;
    
    public function getCartItemsCount(int $cartId): int;
    
    public function clearCart(int $cartId): bool;
    
    public function getCartItemDetails(int $cartItemId): ?array;
    
    public function bulkUpdateCartItems(int $cartId, array $items): bool;
    
    public function syncCartItems(int $cartId, array $items): bool;
    
    public function getCartItemsByProductIds(int $cartId, array $productIds): Collection;
    
    public function getCartItemByProduct(int $cartId, int $productId): ?CartItem;
}
