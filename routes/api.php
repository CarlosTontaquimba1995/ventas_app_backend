<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\CartItemController;
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

        // Cart routes (accessible by customers and salespersons)
        Route::middleware('role:customer,salesperson')->prefix('cart')->group(function () {
            Route::get('/items', [CartItemController::class, 'index']);
            Route::post('/items', [CartItemController::class, 'store']);
            Route::put('/items/{id}', [CartItemController::class, 'update']);
            Route::delete('/items/{id}', [CartItemController::class, 'destroy']);
            Route::get('/summary', [CartItemController::class, 'getCartSummary']);
            Route::post('/bulk-update', [CartItemController::class, 'bulkUpdate']);
        });

        // Discount routes (accessible by customers and salespersons)
        Route::middleware('role:customer,salesperson')->prefix('discounts')->group(function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('/validate', [DiscountController::class, 'validateCode']);
            Route::post('/apply', [DiscountController::class, 'apply']);
            Route::delete('/remove/{order}', [DiscountController::class, 'remove']);
        });

        // Admin routes (only for admin role)
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Example admin routes
            // Route::apiResource('users', UserController::class);
            // Route::apiResource('products', ProductController::class);
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
