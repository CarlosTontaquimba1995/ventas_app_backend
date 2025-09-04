<?php

namespace App\Models;

use App\Services\Interfaces\CartServiceInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute()
    {
        $cartService = app(CartServiceInterface::class);
        return $cartService->getCartTotal($this->id);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function addItem(Product $product, int $quantity = 1)
    {
        $cartService = app(CartServiceInterface::class);
        return $cartService->addItemToCart($this->id, $product, $quantity);
    }
    
    public function updateItemQuantity(int $cartItemId, int $quantity): bool
    {
        $cartService = app(CartServiceInterface::class);
        return $cartService->updateCartItemQuantity($cartItemId, $quantity);
    }
    
    public function removeItem(int $cartItemId): bool
    {
        $cartService = app(CartServiceInterface::class);
        return $cartService->removeItemFromCart($cartItemId);
    }
    
    public function clear(): bool
    {
        $cartService = app(CartServiceInterface::class);
        return $cartService->clearCart($this->id);
    }
}
