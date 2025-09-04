<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Models\User;
use App\Repositories\Interfaces\OfferRepositoryInterface;
use App\Services\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountController extends Controller
{
    protected DiscountService $discountService;
    protected ?User $user;
    protected OfferRepositoryInterface $offerRepository;

    public function __construct(
        DiscountService $discountService,
        OfferRepositoryInterface $offerRepository
    ) {
        $this->discountService = $discountService;
        $this->offerRepository = $offerRepository;
        $this->user = auth('api')->user();
    }

    /**
     * Get all active offers
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $offers = $this->offerRepository->getActiveOffers();
        return response()->json([
            'success' => true,
            'data' => OfferResource::collection($offers)
        ]);
    }

    /**
     * Validate an offer code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

$user = auth('api')->user();
        $result = $this->discountService->validateOfferCode($request->code, $user);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new OfferResource($result['offer']),
            'message' => $result['message']
        ]);
    }

    /**
     * Apply a discount to the current cart/order
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'offer_code' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $order = $user->orders()->findOrFail($request->order_id);

        // Check if the order is eligible for discounts
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Discounts can only be applied to pending orders.'
            ], 422);
        }

        $result = $this->discountService->applyBestDiscount(
            $order,
            $request->offer_code
        );

        // Update the order with the discount
        $order->update([
            'discount' => $result['discount_amount'],
            'total' => $order->subtotal + $order->tax + $order->shipping_cost - $result['discount_amount']
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'discount_amount' => $result['discount_amount'],
                'new_total' => $order->total,
                'applied_offers' => OfferResource::collection($result['applied_offers'])
            ],
            'message' => 'Discount applied successfully.'
        ]);
    }

    /**
     * Remove a discount from an order
     *
     * @param string $orderId
     * @return JsonResponse
     */
    public function remove(string $orderId): JsonResponse
    {
$order = $this->user->orders()->findOrFail($orderId);

        // Only allow removing discounts from pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Discounts can only be removed from pending orders.'
            ], 422);
        }

        // Reset the discount
        $order->update([
            'discount' => 0,
            'total' => $order->subtotal + $order->tax + $order->shipping_cost
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'discount_amount' => 0,
                'new_total' => $order->total
            ],
            'message' => 'Discount removed successfully.'
        ]);
    }
}
