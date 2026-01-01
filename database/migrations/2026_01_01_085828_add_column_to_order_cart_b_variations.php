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
        Schema::table('order_cart_b_variations', function (Blueprint $table) {
            $table->foreignId('order_cart_b_id')->nullable()->constrained('order_cart_bundles')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_cart_b_variations', function (Blueprint $table) {
            //
        });
    }
};
