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
        Schema::create('inventory_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('purchase_stores')->onUpdate('cascade')->onDelete('set null'); 
            $table->integer("product_num")->default(0);
            $table->integer("total_quantity")->default(0);
            $table->float("cost")->default(0); 
            $table->enum("status", ["current", "final", "adjusted"])->default("current"); 
            $table->timestamps();
        });
        
        Schema::dropIfExists('inventory_product_histories');
        Schema::dropIfExists('inventory_material_histories');
        Schema::dropIfExists('inventory_histories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_lists');
    }
};
