<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Services\Interfaces\OrderItemServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderItemService implements OrderItemServiceInterface
{
    public function __construct(
        private OrderItemRepositoryInterface $orderItemRepository
    ) {}

    public function createOrderItem(array $data): OrderItem
    {
        // Calculate total if not provided
        if (!isset($data['total'])) {
            $data['total'] = $this->calculateItemTotal(
                $data['price'],
                $data['quantity']
            );
        }
        
        return $this->orderItemRepository->create($data);
    }

    public function getOrderItem(int $id): ?OrderItem
    {
        return $this->orderItemRepository->findById($id);
    }

    public function getOrderItemsByOrder(int $orderId, int $perPage = 10): LengthAwarePaginator
    {
        // Convert collection to paginator since the repository returns a collection
        $items = $this->orderItemRepository->getByOrderId($orderId);
        return new LengthAwarePaginator(
            $items,
            $items->count(),
            $perPage,
            1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function updateOrderItem(int $id, array $data): ?OrderItem
    {
        $item = $this->getOrderItem($id);
        if (!$item) {
            return null;
        }

        // Recalculate total if price or quantity changes
        if (isset($data['price']) || isset($data['quantity'])) {
            $price = $data['price'] ?? $item->price;
            $quantity = $data['quantity'] ?? $item->quantity;
            $data['total'] = $this->calculateItemTotal($price, $quantity);
        }
        
        return $this->orderItemRepository->update($id, $data) ? $this->getOrderItem($id) : null;
    }

    public function deleteOrderItem(int $id): bool
    {
        $item = $this->getOrderItem($id);
        if (!$item) {
            return false;
        }
        
        try {
            return $this->orderItemRepository->deleteById($id);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function calculateItemTotal(float $price, int $quantity): float
    {
        return round($price * $quantity, 2);
    }

    public function getOrderItemsSummary(int $orderId): array
    {
        $items = $this->orderItemRepository->getByOrderId($orderId);
        
        $subtotal = $items->sum('total');
        $tax = $subtotal * 0.16; // 16% tax as an example
        $total = $subtotal + $tax;
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'item_count' => $items->count()
        ];
    }
}
