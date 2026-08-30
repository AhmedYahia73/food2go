<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exclude_carts')) {
            Schema::create('exclude_carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_cart_id');
                $table->unsignedBigInteger('exclude_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();

                $table->foreign('product_cart_id')->references('id')->on('product_carts')->onDelete('cascade');
                $table->foreign('exclude_id')->references('id')->on('exclude_products')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exclude_carts');
    }
};
