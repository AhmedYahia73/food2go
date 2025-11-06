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
        Schema::create('material_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('material_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('store_id')->nullable()->constrained('purchase_stores')->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('unit_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->float("quantity");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_stocks');
    }
};
