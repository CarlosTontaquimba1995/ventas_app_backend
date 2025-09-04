<?php

namespace App\Services\Interfaces;

use App\Models\Order;

interface OfferServiceInterface
{
    /**
     * Record that an offer was used for an order
     */
    public function recordOfferUsage(int $offerId, int $userId, int $orderId, float $discountAmount): void;

    /**
     * Calculate discount for a given offer and order
     */
    public function calculateDiscount(int $offerId, Order $order): float;

    /**
     * Check if an offer is valid for a user
     */
    public function isValidForUser(int $offerId, int $userId): bool;

    /**
     * Get all active offers
     */
    public function getActiveOffers();
}
