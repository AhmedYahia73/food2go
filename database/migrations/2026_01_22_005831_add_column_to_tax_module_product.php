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
        Schema::table('tax_module_product', function (Blueprint $table) {
            //
            $table->dropForeign(['tax_module_id']);

            $table->foreign('tax_module_id')
            ->references('id')
            ->on('tax_modules') 
            ->onDelete('cascade')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_module_product', function (Blueprint $table) {
            $table->foreign('tax_module_id')
                  ->references('id')
                  ->on('tax_module_branches')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

    }
};
