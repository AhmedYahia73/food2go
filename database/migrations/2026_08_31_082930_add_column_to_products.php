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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean("product_time")->default(false);
            $table->integer("unit_time")->nullable();
            $table->boolean("extra_time")->default(false);
            $table->integer("extra_unit_time")->nullable();
            $table->decimal("extra_time_price", 10, 2)->nullable();
            $table->integer("min_time")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
