<?php

namespace App\Repositories\Interfaces;

use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<CartItem>
 */
interface CartItemRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCartId(int $cartId): Collection;
    
    public function getByProductId(int $productId): Collection;
    
    public function getCartItem(int $cartId, int $productId): ?CartItem;
    
    public function updateQuantity(int $cartItemId, int $quantity): bool;
    
    public function getCartTotal(int $cartId): float;
    
    public function getCartItemsCount(int $cartId): int;
    
    public function clearCart(int $cartId): bool;
    
    public function getCartItemsWithDetails(int $cartId): Collection;
    
    public function getCartItemDetails(int $cartItemId): ?array;
    
    public function bulkUpdateQuantities(array $items): bool;
    
    public function getCartItemsByProductIds(int $cartId, array $productIds): Collection;
}
