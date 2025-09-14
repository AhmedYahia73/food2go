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
        Schema::create('purchase_wasteds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('purchase_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('purchase_products')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained('purchase_stores')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('status', ['pending', 'approve', 'reject']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_wasteds');
    }
};
