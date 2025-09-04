<?php

namespace App\Repositories\Eloquent;

use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<OrderItem>
 */
class OrderItemRepository extends BaseRepository implements OrderItemRepositoryInterface
{
    /**
     * @var OrderItem
     */
    protected $model;

    public function __construct(OrderItem $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function getByOrderId(int $orderId): Collection
    {
        return $this->model->where('order_id', $orderId)->get();
    }

    public function getByProductId(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }

    public function getOrderItemsWithDetails(int $orderId): Collection
    {
        return $this->model->with(['product.images'])
            ->where('order_id', $orderId)
            ->get();
    }

    public function getOrderItemDetails(int $orderItemId): ?array
    {
        $item = $this->model->with(['product.images', 'order'])->find($orderItemId);
        
        if (!$item) {
            return null;
        }

        return [
            'id' => $item->id,
            'order_id' => $item->order_id,
            'order_status' => $item->order->status ?? null,
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'price' => $item->price,
            'quantity' => $item->quantity,
            'total' => $item->total,
            'image' => $item->product->images->first()?->url ?? null,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at
        ];
    }

    public function getBestSellingProducts(int $limit = 10): Collection
    {
        return $this->model->select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->with(['product'])
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getProductSalesData(int $productId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->model->where('product_id', $productId);
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $result = $query->select(
                DB::raw('COUNT(DISTINCT order_id) as orders_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->first();
            
        return [
            'product_id' => $productId,
            'orders_count' => (int) ($result->orders_count ?? 0),
            'total_quantity' => (int) ($result->total_quantity ?? 0),
            'total_revenue' => (float) ($result->total_revenue ?? 0),
            'average_order_value' => $result ? 
                (float) ($result->total_revenue / max(1, $result->orders_count)) : 0
        ];
    }

    public function getTotalSalesByProduct(int $productId): float
    {
        return (float) $this->model->where('product_id', $productId)
            ->sum('total');
    }

    public function getTotalQuantitySoldByProduct(int $productId): int
    {
        return (int) $this->model->where('product_id', $productId)
            ->sum('quantity');
    }

    public function getSalesDataByDateRange(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(DISTINCT order_id) as orders_count'),
            DB::raw('SUM(quantity) as items_sold'),
            DB::raw('SUM(total) as revenue')
        )
        ->groupBy('date')
        ->orderBy('date');
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        return $query->get();
    }

    public function getTopSellingProducts(int $limit = 5, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue')
            )
            ->groupBy('product_id', 'product_name')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit);
            
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        return $query->get();
    }
}
