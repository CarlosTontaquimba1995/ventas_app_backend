<?php

namespace App\Services\Interfaces;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceInterface
{
    /**
     * Create a new order from user's cart
     */
    public function createOrderFromCart(User $user, array $shippingData, array $billingData = null): Order;
    
    /**
     * Get order details by order number
     */
    public function getOrderDetails(string $orderNumber): ?Order;
    
    /**
     * Get paginated list of user's orders
     */
    public function getUserOrders(User $user, int $perPage = 10): LengthAwarePaginator;
    
    /**
     * Get orders by status
     */
    public function getOrdersByStatus(string $status, $query = null);
    
    /**
     * Update order status
     */
    public function updateOrderStatus($order, string $status): bool;
    
    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): Collection;
    
    /**
     * Get orders summary
     */
    public function getOrdersSummary(): array;
    
    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(array $items): array;
    
    /**
     * Process payment for an order
     */
    public function processPayment(Order $order, array $paymentData): bool;
    
    /**
     * Send order confirmation
     */
    public function sendOrderConfirmation(Order $order): void;
}
