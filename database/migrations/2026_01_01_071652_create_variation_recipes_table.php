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
        Schema::create('variation_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_id')->nullable()->constrained("variation_products")->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('option_id')->nullable()->constrained("option_products")->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained("units")->onUpdate('cascade')->onDelete('set null');
            $table->integer("weight");
            $table->foreignId('store_category_id')->nullable()->constrained("purchase_categories")->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('store_product_id')->nullable()->constrained("purchase_products")->onUpdate('cascade')->onDelete('cascade');
            $table->boolean("status")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variation_recipes');
    }
};
