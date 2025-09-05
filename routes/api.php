<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CartController as V1CartController;
use App\Http\Controllers\Api\V1\CartItemController as V1CartItemController;
use App\Http\Controllers\Api\V1\CategoryController as V1CategoryController;
use App\Http\Controllers\Api\V1\DiscountController as V1DiscountController;
use Illuminate\Support\Facades\Route;

// Simple test route to verify API is working
Route::get('/test-api', function () {
    return response()->json(['message' => 'API is working!']);
});

// Test route to verify API is working
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Routes
Route::group([], function () {
    // Public routes (no authentication required)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
    });

    // Protected routes (require authentication)
    Route::middleware(['jwt.verify'])->group(function () {
        // Common authenticated user routes (all roles)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        // Category routes with role-based access control
        Route::middleware('jwt.verify')->prefix('categories')->group(function () {
            // Read routes - only for customers
            Route::middleware('role:customer')->group(function () {
                Route::get('/', [V1CategoryController::class, 'index']);
                Route::get('/{id}', [V1CategoryController::class, 'show']);
                Route::get('/slug/{slug}', [V1CategoryController::class, 'getBySlug']);
                // Get category with children
                Route::get('categories/{id}/children', [V1CategoryController::class, 'showWithChildren'])
                    ->name('categories.children');
            });

            // Write routes - only for admin
            Route::middleware('role:admin')->group(function () {
                Route::post('/', [V1CategoryController::class, 'store']);
                Route::put('/{id}', [V1CategoryController::class, 'update']);
                Route::delete('/{id}', [V1CategoryController::class, 'destroy']);
            });
        });

        // Cart routes with role-based access control
        Route::middleware('jwt.verify')->prefix('cart')->group(function () {

            // Read routes - only for customers
            Route::middleware('role:customer')->group(function () {
                Route::get('/items', [V1CartItemController::class, 'index']);
                Route::get('/summary', [V1CartItemController::class, 'getCartSummary']);
            });

            // Write routes - only for admin
            Route::middleware('role:admin')->group(function () {
                Route::post('/items', [V1CartItemController::class, 'store']);
                Route::put('/items/{id}', [V1CartItemController::class, 'update']);
                Route::delete('/items/{id}', [V1CartItemController::class, 'destroy']);
                Route::post('/bulk-update', [V1CartItemController::class, 'bulkUpdate']);
            });
        });

        // Discount routes with role-based access control
        Route::middleware('jwt.verify')->prefix('discounts')->group(function () {

            // Read routes - only for customers
            Route::middleware('role:customer')->group(function () {
                Route::get('/', [V1DiscountController::class, 'index']);
            });

            // Write routes - only for admin
            Route::middleware('role:admin')->group(function () {
                Route::post('/validate', [V1DiscountController::class, 'validateCode']);
                Route::post('/apply', [V1DiscountController::class, 'apply']);
                Route::delete('/remove/{order}', [V1DiscountController::class, 'remove']);
            });
        });

        // Salesperson routes (only for salesperson role)
        Route::middleware('role:salesperson')->prefix('sales')->group(function () {
            // Example salesperson routes
            // Route::get('/customers', [SalesController::class, 'getCustomers']);
            // Route::post('/orders', [SalesController::class, 'createOrder']);
        });

        // Customer routes (only for customer role)
        Route::middleware('role:customer')->prefix('customer')->group(function () {
            // Example customer routes
            // Route::get('/orders', [OrderController::class, 'customerOrders']);
            // Route::post('/profile/update', [ProfileController::class, 'update']);
        });
    });
});
