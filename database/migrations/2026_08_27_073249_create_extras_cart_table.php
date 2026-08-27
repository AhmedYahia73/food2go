<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extras_cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_cart_id');
            $table->unsignedBigInteger('extra_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('product_cart_id')->references('id')->on('product_carts')->onDelete('cascade');
            $table->foreign('extra_id')->references('id')->on('extra_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extras_cart');
    }
};
