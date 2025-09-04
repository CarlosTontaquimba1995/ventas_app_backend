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
        Schema::create('offer_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            // Store the offer details at the time of application
            $table->string('offer_name');
            $table->string('offer_code')->nullable();
            $table->string('offer_type');
            $table->decimal('discount_value', 10, 2);
            
            // Amount of discount applied to this order
            $table->decimal('discount_amount', 10, 2);
            
            // Additional metadata
            $table->json('applied_to')->nullable(); // Which items/categories this was applied to
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Composite unique key to prevent duplicate applications
            $table->unique(['offer_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_order');
    }
};
