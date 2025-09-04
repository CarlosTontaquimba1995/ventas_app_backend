<?php

namespace App\Services\Interfaces;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderItemServiceInterface
{
    /**
     * Create a new order item
     */
    public function createOrderItem(array $data): OrderItem;
    
    /**
     * Get order item by ID
     */
    public function getOrderItem(int $id): ?OrderItem;
    
    /**
     * Get all order items for an order
     */
    public function getOrderItemsByOrder(int $orderId, int $perPage = 10): LengthAwarePaginator;
    
    /**
     * Update an order item
     */
    public function updateOrderItem(int $id, array $data): ?OrderItem;
    
    /**
     * Delete an order item
     */
    public function deleteOrderItem(int $id): bool;
    
    /**
     * Calculate item total
     */
    public function calculateItemTotal(float $price, int $quantity): float;
    
    /**
     * Get order items summary
     */
    public function getOrderItemsSummary(int $orderId): array;
}
