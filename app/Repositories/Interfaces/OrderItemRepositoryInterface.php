<?php

namespace App\Repositories\Interfaces;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<OrderItem>
 */
interface OrderItemRepositoryInterface extends BaseRepositoryInterface
{
    public function getByOrderId(int $orderId): Collection;
    
    public function getByProductId(int $productId): Collection;
    
    public function getOrderItemsWithDetails(int $orderId): Collection;
    
    public function getOrderItemDetails(int $orderItemId): ?array;
    
    public function getBestSellingProducts(int $limit = 10): Collection;
    
    public function getProductSalesData(int $productId, ?string $startDate = null, ?string $endDate = null): array;
    
    public function getTotalSalesByProduct(int $productId): float;
    
    public function getTotalQuantitySoldByProduct(int $productId): int;
    
    public function getSalesDataByDateRange(?string $startDate = null, ?string $endDate = null): Collection;
    
    public function getTopSellingProducts(int $limit = 5, ?string $startDate = null, ?string $endDate = null): Collection;
}
