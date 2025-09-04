<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Interfaces\CartItemRepositoryInterface;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\Interfaces\CartItemServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CartItemService implements CartItemServiceInterface
{
    public function __construct(
        private CartItemRepositoryInterface $cartItemRepository,
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * @inheritDoc
     */
    public function createCart(int $userId): Cart
    {
        return $this->cartRepository->createForUser($userId);
    }
    
    public function getCartItems(int $cartId): Collection
    {
        return $this->cartItemRepository->getCartItemsWithDetails($cartId);
    }

    public function addItemToCart(int $cartId, int $productId, int $quantity, float $price): ?CartItem
    {
        // Check if product exists and is active
        $product = $this->productRepository->findById($productId);
        if (!$product || !$product->is_active) {
            return null;
        }

        // Check if item already exists in cart
        $existingItem = $this->cartItemRepository->getCartItem($cartId, $productId);
        
        if ($existingItem) {
            // Update quantity if item exists
            $newQuantity = $existingItem->quantity + $quantity;
            $this->cartItemRepository->updateQuantity($existingItem->id, $newQuantity);
            return $existingItem->fresh(['product']);
        }

        // Create new cart item
        return $this->cartItemRepository->create([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price
        ]);
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            return $this->removeItemFromCart($cartItemId) ? null : null;
        }

        $updated = $this->cartItemRepository->updateQuantity($cartItemId, $quantity);
        return $updated ? $this->cartItemRepository->findById($cartItemId, ['*'], ['product']) : null;
    }

    public function removeItemFromCart(int $cartItemId): bool
    {
        return $this->cartItemRepository->deleteById($cartItemId);
    }

    public function getCartTotal(int $cartId): float
    {
        return $this->cartItemRepository->getCartTotal($cartId);
    }

    public function getCartItemsCount(int $cartId): int
    {
        return $this->cartItemRepository->getCartItemsCount($cartId);
    }

    public function clearCart(int $cartId): bool
    {
        return $this->cartItemRepository->clearCart($cartId);
    }

    public function getCartItemDetails(int $cartItemId): ?array
    {
        return $this->cartItemRepository->getCartItemDetails($cartItemId);
    }

    public function bulkUpdateCartItems(int $cartId, array $items): bool
    {
        $validItems = [];
        foreach ($items as $item) {
            if (!isset($item['id'], $item['quantity']) || $item['quantity'] <= 0) {
                continue;
            }
            $validItems[] = [
                'id' => (int)$item['id'],
                'quantity' => (int)$item['quantity']
            ];
        }

        if (empty($validItems)) {
            return true;
        }

        return $this->cartItemRepository->bulkUpdateQuantities($validItems);
    }

    public function syncCartItems(int $cartId, array $items): bool
    {
        return $this->cartItemRepository->transaction(function () use ($cartId, $items) {
            // Clear existing items
            $this->cartItemRepository->clearCart($cartId);
            
            // Add new items
            foreach ($items as $item) {
                if (!isset($item['product_id'], $item['quantity'], $item['price']) || $item['quantity'] <= 0) {
                    continue;
                }
                
                $this->cartItemRepository->create([
                    'cart_id' => $cartId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            return true;
        });
    }

    public function getCartItemsByProductIds(int $cartId, array $productIds): Collection
    {
        return $this->cartItemRepository->getCartItemsByProductIds($cartId, $productIds);
    }

    public function getCartItemByProduct(int $cartId, int $productId): ?CartItem
    {
        return $this->cartItemRepository->getCartItem($cartId, $productId);
    }
}
