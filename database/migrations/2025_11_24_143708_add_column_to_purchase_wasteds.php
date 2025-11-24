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
        Schema::table('purchase_wasteds', function (Blueprint $table) {
            $table->foreignId('category_material_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('material_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_wasteds', function (Blueprint $table) {
            //
        });
    }
};
