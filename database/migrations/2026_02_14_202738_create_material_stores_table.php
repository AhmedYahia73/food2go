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
        Schema::table('material_stores', function (Blueprint $table) {
            $table->dropForeign("material_stores_product_id_foreign");
            $table->dropColumn("product_id");
            $table->foreignId('product_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('cascade');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_fees', function (Blueprint $table) {
            //
        });
    }
};
