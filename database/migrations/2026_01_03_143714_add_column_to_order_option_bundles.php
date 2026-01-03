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
        Schema::table('order_option_bundles', function (Blueprint $table) {
            //
            $table->foreignId('order_bundle_p_id')->nullable()->constrained('order_bundle_products')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_option_bundles', function (Blueprint $table) {
            //
        });
    }
};
