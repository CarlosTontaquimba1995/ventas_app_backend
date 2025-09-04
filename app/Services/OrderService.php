<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\CartServiceInterface;
use App\Services\Interfaces\OrderServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService implements OrderServiceInterface
{
    protected CartServiceInterface $cartService;
    protected OrderRepositoryInterface $orderRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartServiceInterface $cartService,
        UserRepositoryInterface $userRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartService = $cartService;
        $this->userRepository = $userRepository;
    }

public function createOrderFromCart(User $user, array $shippingData, array $billingData = null): Order
    {
        // Get the user's cart
        $cart = $this->userRepository->getUserCart($user->id);
        
        if (!$cart) {
            throw new \RuntimeException('User does not have an active cart');
        }
        
        // Get cart items to validate
        $cartItems = $this->cartService->getCartItems($cart->id);
        
        // Validate cart has items
        if ($cartItems->isEmpty()) {
            throw new \RuntimeException('Cannot create order from empty cart');
        }
        
        // Prepare order data
        $subtotal = $this->cartService->getCartTotal($cart->id);
        $orderData = [
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'tax' => $subtotal * 0.16,
            'shipping' => 100,
            'discount' => 0,
            'total' => $subtotal * 1.16 + 100,
            'shipping_name' => $shippingData['name'],
            'shipping_email' => $shippingData['email'],
            'shipping_phone' => $shippingData['phone'],
            'shipping_address' => $shippingData['address'],
            'billing_name' => $billingData['name'] ?? $shippingData['name'],
            'billing_address' => $billingData['address'] ?? $shippingData['address'],
            'items' => []
        ];

        // Prepare order items
        foreach ($cartItems as $item) {
            $orderData['items'][] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        }

        // Use repository to handle the transaction
        return $this->orderRepository->transaction(function() use ($orderData, $cart) {
            // Create the order
            $order = $this->orderRepository->createOrder($orderData);
            
            // Clear the cart after successful order creation
            $this->cartService->clearCart($cart->id);
            
            return $order;
        });
    }

    public function getOrderDetails(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByOrderNumber($orderNumber);
    }

public function calculateOrderTotals(array $items): array
    {
        $subtotal = collect($items)->sum(fn($item) => $item['price'] * $item['quantity']);
        $tax = $subtotal * 0.16; // 16% tax as an example
        $shipping = 100; // Flat rate shipping
        $total = $subtotal + $tax + $shipping;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total
        ];
    }

    public function processPayment(Order $order, array $paymentData): bool
    {
        // Implement payment processing logic here
        // This is a placeholder implementation
        return true;
    }

    public function sendOrderConfirmation(Order $order): void
    {
        // Send order confirmation email
        // This is a placeholder implementation
    }

    public function getUserOrders(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->orderRepository->getUserOrders($user->id, $perPage);
    }

    public function updateOrderStatus($order, string $status): bool
    {
        $orderId = $order instanceof Order ? $order->id : $order;
        
        // Add any additional logic when order status changes
        if ($status === 'completed' && $order->status !== 'completed') {
            // Handle order completion logic
        } elseif ($status === 'cancelled' && $order->status !== 'cancelled') {
            // Handle order cancellation logic
        }

        return $this->orderRepository->updateOrderStatus($orderId, $status);
    }

    public function getRecentOrders(int $limit = 5): Collection
    {
        return $this->orderRepository->getRecentOrders($limit);
    }

    public function getOrdersSummary(): array
    {
        return $this->orderRepository->getOrdersSummary();
    }

    public function getOrdersByStatus(string $status, $query = null)
    {
        if ($query) {
            return $this->orderRepository->getOrdersByStatus($status, 10); // Default per page
        }
        return $this->orderRepository->getOrdersByStatus($status);
    }
}
