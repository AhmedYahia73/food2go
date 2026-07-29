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
        Schema::create('addon_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('addon_id')->nullable()->constrained("addons")->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('product_cart_id')->nullable()->constrained("product_carts")->onUpdate('cascade')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addon_carts');
    }
};
