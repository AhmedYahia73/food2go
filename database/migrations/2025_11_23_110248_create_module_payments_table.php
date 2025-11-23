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
        Schema::create('module_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_product_id')->nullable()->constrained('group_products')->onUpdate('cascade')->onDelete('cascade');
            $table->float("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_payments');
    }
};
