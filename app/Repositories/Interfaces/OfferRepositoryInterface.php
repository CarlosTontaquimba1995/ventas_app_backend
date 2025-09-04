<?php

namespace App\Repositories\Interfaces;

use App\Models\Offer;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<Offer>
 */
interface OfferRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code): ?Offer;
    public function getActiveOffers($query = null);
    public function getValidOffersForUser(?int $userId = null, $query = null);
    public function incrementTimesUsed(int $offerId): void;
    public function recordUsage(int $offerId, int $userId, int $orderId): void;
    public function getTimesUsedByUser(int $offerId, int $userId): int;
    public function getValidOffersForOrder(int $orderId): Collection;
    public function isOfferActive(int $offerId): bool;
    public function attachToOrder(int $offerId, int $orderId, float $discountAmount): void;
    
    /**
     * Get the number of times a user has used an offer
     */
    public function getUserUsageCount(int $offerId, int $userId): int;
    
    /**
     * Get the total number of times an offer has been used
     */
    public function getTotalUsageCount(int $offerId): int;
}
