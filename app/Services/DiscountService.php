<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Interfaces\OfferRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Collection;

class DiscountService
{
    protected OfferRepositoryInterface $offerRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        OfferRepositoryInterface $offerRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->offerRepository = $offerRepository;
        $this->userRepository = $userRepository;
    }
    /**
     * Apply the best available discount to an order.
     *
     * @param Order $order
     * @param string|null $offerCode
     * @return array
     */
    public function applyBestDiscount(Order $order, ?string $offerCode = null): array
    {
        $user = $order->user;
        $discounts = collect();
        $appliedOffers = collect();
        $totalDiscount = 0;

        // If an offer code is provided, try to apply it first
        if ($offerCode) {
            $offer = $this->offerRepository->findByCode($offerCode);
            
            if ($offer && $this->isOfferApplicable($offer, $order, $user)) {
                $discountAmount = $offer->calculateDiscount($order);
                if ($discountAmount > 0) {
                    $discounts->push([
                        'offer' => $offer,
                        'amount' => $discountAmount,
                        'type' => $offer->type
                    ]);
                    $totalDiscount += $discountAmount;
                    $appliedOffers->push($offer);
                }
            }
        }

        // If no specific offer was applied, check for automatic offers
        if ($appliedOffers->isEmpty()) {
            $automaticOffers = $this->getAutomaticOffers($order, $user);
            
            foreach ($automaticOffers as $offer) {
                $discountAmount = $offer->calculateDiscount($order);
                if ($discountAmount > 0) {
                    $discounts->push([
                        'offer' => $offer,
                        'amount' => $discountAmount,
                        'type' => $offer->type
                    ]);
                    $totalDiscount += $discountAmount;
                    $appliedOffers->push($offer);
                }
            }
        }

        // Apply the best discount (or combination of discounts)
        $bestDiscount = $this->findBestDiscountCombination($discounts, $order->subtotal);
        
        // Record the applied offers
        foreach ($bestDiscount['offers'] as $offer) {
            $offer->recordUsage($order, $bestDiscount['amounts'][$offer->id] ?? 0);
        }

        return [
            'discount_amount' => $bestDiscount['total'],
            'applied_offers' => $bestDiscount['offers'],
            'discount_details' => $bestDiscount['details']
        ];
    }

    /**
     * Check if an offer is applicable to an order.
     *
     * @param Offer|null $offer
     * @param Order $order
     * @param User|null $user
     * @return bool
     */
    protected function isOfferApplicable(?Offer $offer, Order $order, ?User $user): bool
    {
        if (!$offer || !$offer->isActive()) {
            return false;
        }

        // Check if the offer is valid for the user
        if ($user && !$this->isValidForUser($offer, $user)) {
            return false;
        }

        // Check if the offer is applicable to the order
        if (!$offer->isApplicableToOrder($order)) {
            return false;
        }

        return true;
    }

    /**
     * Check if an offer is valid for a specific user.
     *
     * @param Offer $offer
     * @param User $user
     * @return bool
     */
    protected function isValidForUser(Offer $offer, User $user): bool
    {
        // Check if user has exceeded max uses
        if ($offer->max_uses_per_user) {
            $userUsage = $this->offerRepository->getTimesUsedByUser($offer->id, $user->id);
            if ($userUsage >= $offer->max_uses_per_user) {
                return false;
            }
        }

        // Check if user is eligible based on other criteria
        // (e.g., new customers only, specific customer segments, etc.)
        // Add your custom logic here

        return true;
    }

    /**
     * Get automatic offers that can be applied to an order.
     *
     * @param Order $order
     * @param User|null $user
     * @return Collection
     */
    protected function getAutomaticOffers(Order $order, ?User $user): Collection
    {
        $offers = $this->offerRepository->getValidOffersForUser($user?->id);
        
        return $offers->filter(function ($offer) use ($order, $user) {
            return $this->isOfferApplicable($offer, $order, $user);
        });
    }

    /**
     * Find the best combination of discounts to apply.
     *
     * @param Collection $discounts
     * @param float $orderSubtotal
     * @return array
     */
    protected function findBestDiscountCombination(Collection $discounts, float $orderSubtotal): array
    {
        if ($discounts->isEmpty()) {
            return [
                'total' => 0,
                'offers' => collect(),
                'amounts' => [],
                'details' => []
            ];
        }

        // For now, we'll just return the single best discount
        // In a real application, you might want to implement logic to combine multiple offers
        $bestDiscount = $discounts->sortByDesc('amount')->first();
        
        // Don't allow discount to exceed order subtotal
        $finalAmount = min($bestDiscount['amount'], $orderSubtotal);
        
        return [
            'total' => $finalAmount,
            'offers' => collect([$bestDiscount['offer']]),
            'amounts' => [$bestDiscount['offer']->id => $finalAmount],
            'details' => [
                [
                    'offer_id' => $bestDiscount['offer']->id,
                    'offer_name' => $bestDiscount['offer']->name,
                    'type' => $bestDiscount['type'],
                    'amount' => $finalAmount
                ]
            ]
        ];
    }

    /**
     * Validate if an offer code is valid for a user.
     *
     * @param string $code
     * @param User|null $user
     * @return array
     */
    public function validateOfferCode(string $code, ?User $user = null): array
    {
        $offer = $this->offerRepository->findByCode($code);
        
        if (!$offer) {
            return [
                'valid' => false,
                'message' => 'Invalid offer code.'
            ];
        }

        if (!$offer->isActive()) {
            return [
                'valid' => false,
                'message' => 'This offer is not currently active.'
            ];
        }

        // Check if user has exceeded max uses
        if ($user && $offer->max_uses_per_user) {
            $userUsage = $this->offerRepository->getTimesUsedByUser($offer->id, $user->id);
            if ($userUsage >= $offer->max_uses_per_user) {
                return [
                    'valid' => false,
                    'message' => 'You have already used this offer the maximum number of times.'
                ];
            }
        }

        // Check if offer has reached max uses
        if ($offer->max_uses && $offer->times_used >= $offer->max_uses) {
            return [
                'valid' => false,
                'message' => 'This offer has reached its maximum number of uses.'
            ];
        }

        return [
            'valid' => true,
            'offer' => $offer,
            'message' => 'Offer code applied successfully.'
        ];
    }
}
