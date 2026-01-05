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
        Schema::create('delivery_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('financial_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('cashier_man_id')->nullable()->constrained('cashier_men')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->onUpdate('cascade')->onDelete('set null');
            $table->float("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_balance_histories');
    }
};
