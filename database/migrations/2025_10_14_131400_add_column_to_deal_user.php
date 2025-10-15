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
        Schema::table('deal_user', function (Blueprint $table) {
            $table->enum('order_status', ['preparing', 'done', 'pick_up'])->default("preparing");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deal_user', function (Blueprint $table) {
            //
        });
    }
};
