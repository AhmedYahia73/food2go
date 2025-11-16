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
        Schema::table('discount_module_branches', function (Blueprint $table) {
            $table->enum('tpye', ['all', 'app', 'web'])->default('all');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_module_branches', function (Blueprint $table) {
            //
        });
    }
};
