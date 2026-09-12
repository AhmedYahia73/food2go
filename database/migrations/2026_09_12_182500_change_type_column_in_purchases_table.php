<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('purchases', 'type')) {
            try {
                DB::statement("ALTER TABLE `purchases` MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'product'");
            } catch (\Exception $e) {
                // Fallback to Schema builder
                Schema::table('purchases', function (Blueprint $table) {
                    $table->string('type', 50)->default('product')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
