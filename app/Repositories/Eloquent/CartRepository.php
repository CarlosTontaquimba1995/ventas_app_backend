<?php

namespace App\Repositories\Eloquent;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Interfaces\CartRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Cart>
 */
class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    /**
     * @var Cart
     */
    protected $model;

    public function __construct(Cart $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function getByUser(int $userId): ?Cart
    {
        return $this->model->with(['items.product.images'])
            ->where('user_id', $userId)
            ->first();
    }

    public function createForUser(int $userId): Cart
    {
        return $this->model->create(['user_id' => $userId]);
    }

    public function addItem(int $cartId, int $productId, int $quantity = 1, float $price): bool
    {
        return (bool) DB::transaction(function () use ($cartId, $productId, $quantity, $price) {
            $cart = $this->findById($cartId);
            if (!$cart) {
                return false;
            }
            
            $item = $cart->items()->where('product_id', $productId)->first();

            if ($item) {
                $item->increment('quantity', $quantity);
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            return true;
        });
    }

    public function updateItem(int $cartId, int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $productId);
        }

        $cart = $this->findById($cartId);
        if (!$cart) {
            return false;
        }

        return (bool) $cart->items()
            ->where('product_id', $productId)
            ->update(['quantity' => $quantity]);
    }

    public function removeItem(int $cartId, int $productId): bool
    {
        $cart = $this->findById($cartId);
        if (!$cart) {
            return false;
        }
        
        return (bool) $cart->items()
            ->where('product_id', $productId)
            ->delete();
    }

    public function clear(int $cartId): bool
    {
        $cart = $this->findById($cartId);
        if (!$cart) {
            return false;
        }
        
        return (bool) $cart->items()->delete();
    }

    public function getCartTotal(int $cartId): float
    {
        $cart = $this->getCartWithItems($cartId);
        if (!$cart) {
            return 0.0;
        }
        
        return (float) $cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function getCartItemsCount(int $cartId): int
    {
        $cart = $this->getCartWithItems($cartId);
        return $cart ? $cart->items->sum('quantity') : 0;
    }
    
    public function getCartWithItems(int $cartId): ?Cart
    {
        return $this->model->with(['items.product.images'])->find($cartId);
    }
    
    public function mergeGuestCart(int $userId, array $guestItems): Cart
    {
        $cart = $this->getByUser($userId) ?? $this->createForUser($userId);
        
        if (!empty($guestItems)) {
            foreach ($guestItems as $item) {
                $this->addItem(
                    $cart->id, 
                    $item['product_id'], 
                    $item['quantity'],
                    $item['price']
                );
            }
        }
        
        return $cart->load('items.product.images');
    }
    
    public function getCartSummary(int $cartId): array
    {
        $cart = $this->getCartWithItems($cartId);
        if (!$cart) {
            return [
                'items_count' => 0,
                'subtotal' => 0,
                'tax' => 0,
                'shipping' => 0,
                'discount' => 0,
                'total' => 0,
                'items' => []
            ];
        }
        
        $subtotal = $this->getCartTotal($cartId);
        
        // These would typically come from configuration or a service
        $taxRate = 0.16; // 16% tax rate
        $shippingCost = $subtotal > 0 ? 100 : 0; // Example shipping cost
        
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax + $shippingCost;
        
        return [
            'items_count' => $this->getCartItemsCount($cartId),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shippingCost,
            'discount' => 0, // Would be calculated from applied discounts
            'total' => $total,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->price * $item->quantity,
                    'image' => $item->product->main_image ?? null
                ];
            })
        ];
    }
    
    public function calculateDiscounts(int $cartId, array $appliedOffers = []): array
    {
        $cart = $this->getCartWithItems($cartId);
        if (!$cart) {
            return [
                'discount_amount' => 0,
                'applied_offers' => [],
                'subtotal' => 0,
                'total' => 0
            ];
        }
        
        $subtotal = $this->getCartTotal($cartId);
        $discountAmount = 0;
        $applied = [];
        
        // This is a simplified example - in a real app, you'd have more complex discount logic
        foreach ($appliedOffers as $offer) {
            if (isset($offer['type']) && $offer['type'] === 'percentage' && isset($offer['value'])) {
                $discount = $subtotal * ($offer['value'] / 100);
                $discountAmount += $discount;
                $applied[] = [
                    'code' => $offer['code'] ?? null,
                    'description' => $offer['description'] ?? 'Discount applied',
                    'discount' => $discount
                ];
            }
        }
        
        return [
            'discount_amount' => $discountAmount,
            'applied_offers' => $applied,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discountAmount)
        ];
    }
}
