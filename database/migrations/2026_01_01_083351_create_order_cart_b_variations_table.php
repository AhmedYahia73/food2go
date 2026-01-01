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
        Schema::create('order_cart_b_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_cart_id')->nullable()->constrained('order_carts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('variation_products')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('option_id')->nullable()->constrained('option_products')->onUpdate('cascade')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cart_b_variations');
    }
};
