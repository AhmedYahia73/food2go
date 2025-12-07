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
        Schema::create('inventory_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('purchase_stores')->onUpdate('cascade')->onDelete('set null'); 
            $table->integer("product_num");
            $table->integer("total_quantity");
            $table->float("cost"); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_lists');
    }
};
