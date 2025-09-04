<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Order>
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'user_id' => $data['user_id'],
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'] ?? 0,
                'shipping' => $data['shipping'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total' => $data['total'],
                'notes' => $data['notes'] ?? null,
                'shipping_name' => $data['shipping_name'],
                'shipping_email' => $data['shipping_email'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'],
                'billing_name' => $data['billing_name'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            return $order->load('items');
        });
    }

    public function findById(int $orderId, array $columns = ['*'], array $relations = [])
    {
        return parent::findById($orderId, $columns, $relations);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::with(['user', 'items.product'])
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function getUserOrders(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->with(['items.product'])
            ->latest()
            ->paginate($perPage);
    }

    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $order = $this->findById($orderId);
        if (!$order) {
            return false;
        }
        
        $order->status = $status;
        return $order->save();
    }

    public function getRecentOrders(int $limit = 5): Collection
    {
        return $this->model->with(['user', 'items'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getOrdersSummary(): array
    {
        return [
            'total_orders' => $this->model->count(),
            'total_sales' => $this->model->sum('total'),
            'pending_orders' => $this->model->where('status', 'pending')->count(),
            'completed_orders' => $this->model->where('status', 'completed')->count(),
        ];
    }

    public function getOrderWithItems(string $orderNumber): ?Order
    {
        return $this->model->with('items')
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function getOrdersByStatus(string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->with(['user', 'items'])
            ->where('status', $status)
            ->latest()
            ->paginate($perPage);
    }

    public function getTotalSales(): float
    {
        return (float) $this->model->where('status', 'completed')
            ->sum('total');
    }

    public function getMonthlySales(int $months = 6): array
    {
        $sales = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $month = $date->format('Y-m');

            $total = $this->model->where('status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total');

            $sales[$month] = (float) $total;
        }

        return $sales;
    }

    public function getOrderCountByStatus(string $status = null): int
    {
        $query = $this->model->query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    public function searchOrders(string $query, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('order_number', 'like', "%{$query}%")
            ->orWhere('shipping_name', 'like', "%{$query}%")
            ->orWhere('shipping_email', 'like', "%{$query}%")
            ->with(['user', 'items'])
            ->latest()
            ->paginate($perPage);
    }

    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $lastOrder = $this->model->where('order_number', 'like', "{$prefix}{$date}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $number = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $number = '0001';
        }

        return "{$prefix}{$date}{$number}";
    }
}
