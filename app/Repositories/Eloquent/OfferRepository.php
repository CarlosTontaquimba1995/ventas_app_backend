<?php

namespace App\Repositories\Eloquent;

use App\Models\Offer;
use App\Repositories\Interfaces\OfferRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Offer>
 */
class OfferRepository extends BaseRepository implements OfferRepositoryInterface
{
    /**
     * @var Offer
     */
    protected $model;

    public function __construct(Offer $model)
    {
        parent::__construct($model);
    }
    public function findById(int $id, array $columns = ['*'], array $relations = [])
    {
        return parent::findById($id, $columns, $relations);
    }

    public function findByCode(string $code): ?Offer
    {
        return $this->model->where('code', $code)->first();
    }

    public function getActiveOffers($query = null)
    {
        $query = $query ?? $this->model->newQuery();
        
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }

    public function getValidOffersForUser(?int $userId = null, $query = null)
    {
        $query = $query ?? $this->model->newQuery();
        
        $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('max_uses_per_user')
                  ->orWhereDoesntHave('users', function ($q) use ($userId) {
                      $q->where('user_id', $userId)
                        ->whereRaw('times_used >= max_uses_per_user');
                  });
            });
        }

        // If we received a query builder, we'll return it for further chaining
        // Otherwise, we'll return the collection of results
        return $query;
    }

    public function incrementTimesUsed(int $offerId): void
    {
        $this->model->where('id', $offerId)->increment('times_used');
    }

    public function recordUsage(int $offerId, int $userId, int $orderId): void
    {
        $offer = $this->findById($offerId);
        if (!$offer) {
            return;
        }

        $pivotData = [
            'times_used' => DB::raw('times_used + 1'),
            'last_used_at' => now(),
            'order_ids' => DB::raw("JSON_ARRAY_APPEND(COALESCE(JSON_EXTRACT(order_ids, '$'), '[]'), '$', '{$orderId}')")
        ];

        // Check if this is the first time the user is using this offer
        $existingPivot = $offer->users()->where('user_id', $userId)->first();
        if (!$existingPivot) {
            $pivotData['first_used_at'] = now();
        }

        $offer->users()->syncWithoutDetaching([$userId => $pivotData], false);
    }

    public function getTimesUsedByUser(int $offerId, int $userId): int
    {
        $offer = $this->findById($offerId);
        if (!$offer) {
            return 0;
        }

        $userPivot = $offer->users()->where('user_id', $userId)->first();
        return $userPivot ? $userPivot->pivot->times_used : 0;
    }

    public function getValidOffersForOrder(int $orderId): Collection
    {
        // Get the order with its items
        $order = app(\App\Repositories\Interfaces\OrderRepositoryInterface::class)
            ->findById($orderId, ['*'], ['items']);
            
        if (!$order) {
            return collect();
        }
        
        // Get all active offers
        $offers = $this->getActiveOffers()->get();
        
        // Filter offers that are valid for this order
        return $offers->filter(function ($offer) use ($order) {
            // Check minimum order amount
            if ($offer->min_order_amount && $order->subtotal < $offer->min_order_amount) {
                return false;
            }
            
            // Check if offer is applicable to any product in the order
            $applicableItems = $order->items->filter(function ($item) use ($offer) {
                return $offer->isApplicableToProduct($item->product_id);
            });
            
            return $applicableItems->isNotEmpty();
        });
    }
    
    public function isOfferActive(int $offerId): bool
    {
        $offer = $this->findById($offerId);
        
        if (!$offer || !$offer->is_active) {
            return false;
        }
        
        $now = now();
        
        if ($offer->starts_at && $offer->starts_at->gt($now)) {
            return false;
        }
        
        if ($offer->expires_at && $offer->expires_at->lt($now)) {
            return false;
        }
        
        return true;
    }
    
    public function attachToOrder(int $offerId, int $orderId, float $discountAmount): void
    {
        $offer = $this->findById($offerId);
        if ($offer) {
            $offer->orders()->attach($orderId, [
                'discount_amount' => $discountAmount,
                'applied_at' => now(),
            ]);
        }
    }
    
    /**
     * Get the number of times a user has used an offer
     */
    public function getUserUsageCount(int $offerId, int $userId): int
    {
        return DB::table('offer_user')
            ->where('offer_id', $offerId)
            ->where('user_id', $userId)
            ->count();
    }
    
    /**
     * Get the total number of times an offer has been used
     */
    public function getTotalUsageCount(int $offerId): int
    {
        return DB::table('offer_user')
            ->where('offer_id', $offerId)
            ->count();
    }
}
