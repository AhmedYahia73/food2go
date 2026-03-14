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
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal("start_stock", 10, 2)->default(0);
            $table->decimal("cost", 10, 2)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            //
        });
    }
};
