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
        Schema::create('kitchen_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_order_id')->nullable()->constrained('kitchen_orders')->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('product_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_items');
    }
};
