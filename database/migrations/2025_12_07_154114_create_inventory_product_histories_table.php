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
        Schema::create('inventory_product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('purchase_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('purchase_products')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('inventory_id')->nullable()->constrained('inventory_histories')->onUpdate('cascade')->onDelete('cascade');
            $table->integer("quantity");
            $table->integer("actual_quantity");
            $table->integer("inability");
            $table->float("cost");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_product_histories');
    }
};
