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
        Schema::create('option_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->foreignId('product_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('variation_products')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_products');
    }
};
