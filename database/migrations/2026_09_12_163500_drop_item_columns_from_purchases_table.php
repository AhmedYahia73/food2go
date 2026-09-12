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
        Schema::table('purchases', function (Blueprint $table) {
            $columnsToDrop = [
                'category_id',
                'product_id',
                'unit_id',
                'category_material_id',
                'material_id',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Exception $e) {
                        // Ignore if FK does not exist
                    }
                }
            }

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('purchase_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('purchase_products')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('category_material_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('material_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('set null');
        });
    }
};
