<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            
            // Offer type: percentage, fixed_amount, buy_x_get_y, etc.
            $table->enum('type', ['percentage', 'fixed_amount', 'buy_x_get_y', 'free_shipping']);
            
            // Discount value (percentage or fixed amount)
            $table->decimal('discount_value', 10, 2);
            
            // Offer constraints
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('max_uses_per_user')->nullable();
            
            // Validity period
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Applicability
            $table->enum('apply_to', ['all', 'products', 'categories', 'specific_products'])->default('all');
            
            // For product/category specific offers
            $table->json('applicable_products')->nullable();
            $table->json('applicable_categories')->nullable();
            
            // Usage tracking
            $table->integer('times_used')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
