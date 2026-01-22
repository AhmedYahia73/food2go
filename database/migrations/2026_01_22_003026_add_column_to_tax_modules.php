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
        Schema::table('tax_modules', function (Blueprint $table) {
            $table->dropColumn('tax');
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->onUpdate('cascade')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_modules', function (Blueprint $table) {
            //
        });
    }
};
