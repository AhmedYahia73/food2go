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
        Schema::create('user_debt_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_debt_id')->nullable()->constrained("user_paid_debts")->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('financial_id')->nullable()->constrained("finantiol_acountings")->onUpdate('cascade')->onDelete('cascade');
            $table->float("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_debt_financials');
    }
};
