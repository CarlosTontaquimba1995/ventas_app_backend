<?php

namespace App\Repositories\Interfaces;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Cart>
 */
interface CartRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUser(int $userId): ?Cart;
    
    public function createForUser(int $userId): Cart;
    
    public function addItem(int $cartId, int $productId, int $quantity = 1, float $price): bool;
    
    public function updateItem(int $cartId, int $productId, int $quantity): bool;
    
    public function removeItem(int $cartId, int $productId): bool;
    
    public function clear(int $cartId): bool;
    
    public function getCartTotal(int $cartId): float;
    
    public function getCartItemsCount(int $cartId): int;
    
    public function getCartWithItems(int $cartId): ?Cart;
    
    public function mergeGuestCart(int $userId, array $guestItems): Cart;
    
    public function getCartSummary(int $cartId): array;
    
    public function calculateDiscounts(int $cartId, array $appliedOffers = []): array;
}
