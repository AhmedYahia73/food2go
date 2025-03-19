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
        Schema::create('captain_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain_order_id')->nullable()->constrained('captain_orders')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cafe_location_id')->nullable()->constrained('cafe_locations')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captain_location');
    }
};
