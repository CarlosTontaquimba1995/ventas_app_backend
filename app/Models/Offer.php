<?php

namespace App\Models;

use App\Repositories\Interfaces\OfferRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class Offer extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'discount_value',
        'min_order_amount',
        'max_uses',
        'max_uses_per_user',
        'starts_at',
        'expires_at',
        'is_active',
        'apply_to',
        'applicable_products',
        'applicable_categories',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
    ];

    // Relationships
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->withPivot([
                'offer_name',
                'offer_code',
                'offer_type',
                'discount_value',
                'discount_amount',
                'applied_to',
                'notes',
            ])
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'times_used',
                'first_used_at',
                'last_used_at',
                'order_ids',
            ])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        $offerRepository = App::make(OfferRepositoryInterface::class);
        return $offerRepository->getActiveOffers($query);
    }
    
    /**
     * Check if the offer is active
     */
    public function isActive(): bool
    {
        $offerRepository = App::make(OfferRepositoryInterface::class);
        return $offerRepository->isOfferActive($this->id);
    }

    public function scopeValidForUser($query, User $user)
    {
        $offerRepository = App::make(OfferRepositoryInterface::class);
        return $offerRepository->getValidOffersForUser($user->id, $query);
    }

    // Methods
    public function isApplicableToOrder(Order $order): bool
    {
        // Check minimum order amount
        if ($this->min_order_amount && $order->subtotal < $this->min_order_amount) {
            return false;
        }

        // Check if offer applies to specific products/categories
        if ($this->apply_to === 'products' && $this->applicable_products) {
            $orderProductIds = $order->items->pluck('product_id')->toArray();
            return !empty(array_intersect($orderProductIds, $this->applicable_products));
        }

        if ($this->apply_to === 'categories' && $this->applicable_categories) {
            $orderCategoryIds = $order->items->pluck('product.category_id')->unique()->toArray();
            return !empty(array_intersect($orderCategoryIds, $this->applicable_categories));
        }

        return true;
    }

    /**
     * Calculate discount for this offer on the given order
     * 
     * @param Order $order
     * @return float
     */
    public function calculateDiscount(Order $order): float
    {
        $offerService = app(\App\Services\Interfaces\OfferServiceInterface::class);
        return $offerService->calculateDiscount($this->id, $order);
    }

    /**
     * Record that this offer was used for an order
     * 
     * @param Order $order
     * @param float $discountAmount
     * @return void
     */
    public function recordUsage(Order $order, float $discountAmount): void
    {
        $offerService = app(\App\Services\Interfaces\OfferServiceInterface::class);
        $offerService->recordOfferUsage($this->id, $order->user_id, $order->id, $discountAmount);
    }

    /**
     * Check if this offer is valid for a user
     * 
     * @param int $userId
     * @return bool
     */
    public function isValidForUser(int $userId): bool
    {
        $offerService = app(\App\Services\Interfaces\OfferServiceInterface::class);
        return $offerService->isValidForUser($this->id, $userId);
    }
}
