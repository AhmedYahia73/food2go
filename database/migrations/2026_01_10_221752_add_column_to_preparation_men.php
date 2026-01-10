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
        Schema::table('preparation_men', function (Blueprint $table) {
            $table->string("print_name")->nullable();
            $table->string("print_ip")->nullable();
            $table->string("print_port")->nullable();
            $table->enum("print_type", ["usb", "network"])->default("usb");
            $table->boolean("print_status")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preparation_men', function (Blueprint $table) {
            //
        });
    }
};
