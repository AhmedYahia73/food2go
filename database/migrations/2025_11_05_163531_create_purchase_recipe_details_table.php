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
        Schema::create('purchase_recipe_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->nullable()->constrained('purchase_recipes')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('store_category_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('store_product_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('set null');
            $table->float("weight");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_recipe_details');
    }
};
