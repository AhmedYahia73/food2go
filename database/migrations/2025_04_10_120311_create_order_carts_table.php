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
        Schema::create('order_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->onUpdate('cascade')->onDelete('cascade');
            $table->text('cart')->nullable();
            $table->string('date')->nullable();
            $table->float('amount')->nullable();
            $table->float('total_tax')->nullable();
            $table->float('total_discount')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_carts');
    }
};
