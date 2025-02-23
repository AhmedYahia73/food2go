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
        Schema::table('company_infos', function (Blueprint $table) {
            $table->string('phone2')->nullable();
            $table->string('watts')->nullable();
            $table->string('android_link')->nullable();
            $table->string('ios_link')->nullable();
            $table->boolean('order_online')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_infos', function (Blueprint $table) {
            //
        });
    }
};
