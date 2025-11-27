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
        Schema::table('module_financials', function (Blueprint $table) {
            $table->foreignId('cashier_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cahier_man_id')->nullable()->constrained('cashier_men')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_financials', function (Blueprint $table) {
            //
        });
    }
};
