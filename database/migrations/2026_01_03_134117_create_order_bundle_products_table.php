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
        Schema::create('order_bundle_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_bundle_id')->nullable()->constrained('order_bundles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_bundle_products');
    }
};
