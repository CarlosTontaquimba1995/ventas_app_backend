<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\DiscountService;
use App\Services\OfferService;
use App\Services\OrderService;
use App\Services\OrderItemService;
use App\Services\ProductService;
use App\Services\UserService;
use App\Services\CartItemService;
use App\Services\Interfaces\CartServiceInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use App\Services\Interfaces\OfferServiceInterface;
use App\Services\Interfaces\OrderServiceInterface;
use App\Services\Interfaces\OrderItemServiceInterface;
use App\Services\Interfaces\ProductServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Interfaces\CartItemServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Service bindings
        $this->app->bind(CartItemServiceInterface::class, function ($app) {
            return new CartItemService(
                $app->make('App\Repositories\Interfaces\CartItemRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\CartRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\ProductRepositoryInterface')
            );
        });
        
        $this->app->bind(AuthService::class, function ($app) {
            return new AuthService(
                $app->make('App\Repositories\Interfaces\UserRepositoryInterface')
            );
        });

        $this->app->bind(CartServiceInterface::class, function ($app) {
            return new CartService(
                $app->make('App\Repositories\Interfaces\CartRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\CartItemRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\ProductRepositoryInterface')
            );
        });

        $this->app->bind(CategoryServiceInterface::class, function ($app) {
            return new CategoryService(
                $app->make('App\Repositories\Interfaces\CategoryRepositoryInterface')
            );
        });

        $this->app->bind(DiscountService::class, function ($app) {
            return new DiscountService(
                $app->make('App\Repositories\Interfaces\OfferRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\UserRepositoryInterface')
            );
        });

        $this->app->bind(OfferServiceInterface::class, function ($app) {
            return new OfferService(
                $app->make('App\Repositories\Interfaces\OfferRepositoryInterface')
            );
        });

        $this->app->bind(OrderServiceInterface::class, function ($app) {
            return new OrderService(
                $app->make('App\Repositories\Interfaces\OrderRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\OrderItemRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\UserRepositoryInterface')
            );
        });

        $this->app->bind(OrderItemServiceInterface::class, function ($app) {
            return new OrderItemService(
                $app->make('App\Repositories\Interfaces\OrderItemRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\ProductRepositoryInterface')
            );
        });

        $this->app->bind(ProductServiceInterface::class, function ($app) {
            return new ProductService(
                $app->make('App\Repositories\Interfaces\ProductRepositoryInterface'),
                $app->make('App\Repositories\Interfaces\CategoryRepositoryInterface')
            );
        });

        $this->app->bind(UserServiceInterface::class, function ($app) {
            return new UserService(
                $app->make('App\Repositories\Interfaces\UserRepositoryInterface')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
