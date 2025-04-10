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
            $table->dropColumn('modules');
            $table->boolean('take_away')->after('id')->default(1);
            $table->boolean('dine_in')->after('take_away')->default(1);
            $table->boolean('delivery')->after('dine_in')->default(1);
            $table->boolean('car_slow')->after('delivery')->default(1);
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
