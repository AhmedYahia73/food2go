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
        Schema::create('order_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('financial_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cashier_man_id')->nullable()->constrained('cashier_men')->onUpdate('cascade')->onDelete('cascade');
            $table->float('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_financials');
    }
};
