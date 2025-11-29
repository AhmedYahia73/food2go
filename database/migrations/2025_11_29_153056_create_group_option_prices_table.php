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
        Schema::create('group_option_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->nullable()->constrained('option_products')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('group_product_id')->nullable()->constrained('group_products')->onUpdate('cascade')->onDelete('cascade');
            $table->float("price");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_option_prices');
    }
};
