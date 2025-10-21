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
        Schema::create('user_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('order_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('cashier_id')->nullable()->constrained("cashier_men")->onUpdate('cascade')->onDelete('set null');
            $table->float("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_dues');
    }
};
