<?php

namespace App\Repositories\Eloquent;

use App\Models\CartItem;
use App\Repositories\Interfaces\CartItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<CartItem>
 */
class CartItemRepository extends BaseRepository implements CartItemRepositoryInterface
{
    /**
     * @param CartItem $model
     */
    public function __construct(CartItem $model)
    {
        parent::__construct($model);
    }
    public function getByCartId(int $cartId): Collection
    {
        return $this->model->where('cart_id', $cartId)->get();
    }
    
    public function getByProductId(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }
    
    public function getCartItem(int $cartId, int $productId): ?CartItem
    {
        return $this->model
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();
    }
    
    public function updateQuantity(int $cartItemId, int $quantity): bool
    {
        return $this->update($cartItemId, ['quantity' => $quantity]);
    }
    
    public function getCartTotal(int $cartId): float
    {
        return (float) $this->model->where('cart_id', $cartId)
            ->select(DB::raw('SUM(price * quantity) as total'))
            ->value('total') ?? 0;
    }
    
    public function getCartItemsCount(int $cartId): int
    {
        return $this->model->where('cart_id', $cartId)
            ->sum('quantity');
    }
    
    public function clearCart(int $cartId): bool
    {
        return $this->getQuery()
            ->where('cart_id', $cartId)
            ->delete() > 0;
    }
    
    public function getCartItemsWithDetails(int $cartId): Collection
    {
        return $this->model->with(['product.images'])
            ->where('cart_id', $cartId)
            ->get();
    }
    
    public function getCartItemDetails(int $cartItemId): ?array
    {
        $item = $this->model->with(['product.images'])->find($cartItemId);
        return $item ? $item->toArray() : null;
    }
    
    public function bulkUpdateQuantities(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                if (!isset($item['id'], $item['quantity'])) {
                    continue;
                }
                $this->update($item['id'], ['quantity' => $item['quantity']]);
            }
            return true;
        });
    }
    
    public function getCartItemsByProductIds(int $cartId, array $productIds): Collection
    {
        return $this->model->where('cart_id', $cartId)
            ->whereIn('product_id', $productIds)
            ->get();
    }
    
}
