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
        Schema::table('order_carts', function (Blueprint $table) {
            // prepration_status
            $table->enum('prepration_status', ['watting', 'preparing', 'done', 'pick_up'])
            ->default('watting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_carts', function (Blueprint $table) {
            //
        });
    }
};
