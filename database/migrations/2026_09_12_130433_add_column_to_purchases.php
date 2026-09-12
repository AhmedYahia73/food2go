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
            if (!Schema::hasColumn('purchases', 'payment')) {
                $table->decimal('payment', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('purchases', 'due')) {
                $table->decimal('due', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('purchases', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
            if (Schema::hasColumn('purchases', 'due')) {
                $table->dropColumn('due');
            }
            if (Schema::hasColumn('purchases', 'payment')) {
                $table->dropColumn('payment');
            }
        });
    }
};
