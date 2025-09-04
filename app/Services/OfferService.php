<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\Interfaces\OfferRepositoryInterface;
use App\Services\Interfaces\OfferServiceInterface;

class OfferService implements OfferServiceInterface
{
    protected $offerRepository;

    public function __construct(OfferRepositoryInterface $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    /**
     * @inheritDoc
     */
    public function recordOfferUsage(int $offerId, int $userId, int $orderId, float $discountAmount): void
    {
        $this->offerRepository->recordUsage($offerId, $userId, $orderId);
        $this->offerRepository->attachToOrder($offerId, $orderId, $discountAmount);
    }

    /**
     * @inheritDoc
     */
    public function calculateDiscount(int $offerId, Order $order): float
    {
        $offer = $this->offerRepository->findById($offerId);
        
        if (!$offer || !$this->isValidForUser($offerId, $order->user_id)) {
            return 0.0;
        }

        // Implement specific discount calculation logic here
        // For example: buy 2 get 1 free, buy 1 get 1 50% off, etc.
        return 0.0;
    }

    /**
     * @inheritDoc
     */
    public function isValidForUser(int $offerId, int $userId): bool
    {
        $offer = $this->offerRepository->findById($offerId);
        
        if (!$offer || !$offer->isActive()) {
            return false;
        }

        // Check if user has already used this offer
        $usageCount = $this->offerRepository->getUserUsageCount($offerId, $userId);
        
        // Check usage limits
        if ($offer->usage_limit_per_user !== null && $usageCount >= $offer->usage_limit_per_user) {
            return false;
        }

        // Check overall usage limit
        if ($offer->usage_limit !== null) {
            $totalUsage = $this->offerRepository->getTotalUsageCount($offerId);
            if ($totalUsage >= $offer->usage_limit) {
                return false;
            }
        }

        // Check validity period
        $now = now();
        if (($offer->valid_from && $now->lt($offer->valid_from)) || 
            ($offer->valid_until && $now->gt($offer->valid_until))) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getActiveOffers()
    {
        return $this->offerRepository->getActiveOffers();
    }
}
