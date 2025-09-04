<?php

namespace App\Repositories\Interfaces;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<Order>
 */
interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    public function createOrder(array $data): Order;
    
    public function findByOrderNumber(string $orderNumber): ?Order;
    
    public function getUserOrders(int $userId, int $perPage = 10): LengthAwarePaginator;
    
    public function updateOrderStatus(int $orderId, string $status): bool;
    
    public function getRecentOrders(int $limit = 5): Collection;
    
    public function getOrdersSummary(): array;
    
    public function getOrderWithItems(string $orderNumber): ?Order;
    
    public function getOrdersByStatus(string $status, int $perPage = 10): LengthAwarePaginator;
    
    public function getTotalSales(): float;
    
    public function getMonthlySales(int $months = 6): array;
    
    public function getOrderCountByStatus(string $status = null): int;
    
    public function searchOrders(string $query, int $perPage = 10): LengthAwarePaginator;
}
