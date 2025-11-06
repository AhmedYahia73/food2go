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
        Schema::create('k_item_vriations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_item_id')->nullable()->constrained('kitchen_items')->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('variation_id')->nullable()->constrained('variation_products')->onUpdate('cascade')->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('k_item_vriations');
    }
};
