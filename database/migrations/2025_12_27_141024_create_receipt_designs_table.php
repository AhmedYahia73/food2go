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
        Schema::create('receipt_designs', function (Blueprint $table) {
            $table->id();
            $table->boolean("logo")->default(1);
            $table->boolean("name")->default(1);
            $table->boolean("address")->default(1);
            $table->boolean("branch")->default(1);
            $table->boolean("phone")->default(1);
            $table->boolean("cashier_name")->default(1);
            $table->boolean("footer")->default(1);
            $table->boolean("taxes")->default(1);
            $table->boolean("services")->default(1);
            $table->boolean("table_num")->default(1);
            $table->boolean("preparation_num")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_designs');
    }
};
