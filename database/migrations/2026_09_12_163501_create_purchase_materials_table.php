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
        Schema::create('purchase_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('category_material_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('material_id')->constrained('materials')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onUpdate('cascade')->onDelete('set null');
            $table->decimal('count', 10, 2)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_materials');
    }
};
