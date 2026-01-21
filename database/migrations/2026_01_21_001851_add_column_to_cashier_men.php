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
        Schema::table('cashier_men', function (Blueprint $table) {
            $table->boolean("total_tax")->default(1);
            $table->boolean("service_fees")->default(1);
            $table->boolean("enter_amount")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_men', function (Blueprint $table) {
            //
        });
    }
};
