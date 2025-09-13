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
        Schema::create('purchase_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('financial_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('set null');
            $table->float('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_financials');
    }
};
