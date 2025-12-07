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
        Schema::create('inventory_material_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('inventory_id')->nullable()->constrained('inventory_histories')->onUpdate('cascade')->onDelete('cascade');
            $table->integer("quantity_from");
            $table->integer("quantity_to");
            $table->integer("inability");
            $table->float("cost");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_material_histories');
    }
};
