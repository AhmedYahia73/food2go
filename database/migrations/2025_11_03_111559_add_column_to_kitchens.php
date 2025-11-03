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
        Schema::table('kitchens', function (Blueprint $table) {
            $table->string("print_name")->nullable();
            $table->string("print_ip")->nullable();
            $table->boolean("print_status")->default(1);
        });
    }

            // '' => 'required|boolean',
            // '' => 'required',
            // 'print_ip' => 'required',
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kitchens', function (Blueprint $table) {
            //
        });
    }
};
