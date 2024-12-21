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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->boolean('all')->default(0);
            $table->boolean('branch')->default(0);
            $table->boolean('customer')->default(0);
            $table->boolean('web')->default(0);
            $table->boolean('delivery')->default(0);
            $table->boolean('day')->default(0);
            $table->boolean('week')->default(0);
            $table->boolean('until_change')->default(0);
            $table->boolean('customize')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
