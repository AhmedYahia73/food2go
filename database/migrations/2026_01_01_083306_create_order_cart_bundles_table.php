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
        Schema::create('order_cart_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->nullable()->constrained('bundles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('order_cart_id')->nullable()->constrained('order_carts')->onUpdate('cascade')->onDelete('set null');
            $table->integer("count")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cart_bundles');
    }
};
