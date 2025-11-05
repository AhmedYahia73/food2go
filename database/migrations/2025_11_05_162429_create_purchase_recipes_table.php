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
        Schema::create('purchase_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('purchase_products')->onUpdate('cascade')->onDelete('set null'); 
            $table->foreignId('material_category_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('material_product_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('set null');
            $table->float("weight");
            $table->foreignId('unit_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->boolean("status")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_recipes');
    }
};
