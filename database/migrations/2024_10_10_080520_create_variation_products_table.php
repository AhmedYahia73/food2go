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
        Schema::create('variation_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['multiple', 'single']);
            $table->float('min')->nullable();
            $table->float('max')->nullable();
            $table->boolean('required');
            $table->foreignId('product_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variation_products');
    }
};
