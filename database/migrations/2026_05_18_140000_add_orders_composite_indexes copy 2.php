<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Composite index for the main order fetching queries
            if (!$this->indexExists('orders', 'orders_pos_created_at_captain_id_index')) {
                $table->index(['pos', 'created_at', 'captain_id'], 'orders_pos_created_at_captain_id_index');
            }
            if (!$this->indexExists('orders', 'orders_branch_id_pos_created_at_index')) {
                $table->index(['branch_id', 'pos', 'created_at'], 'orders_branch_id_pos_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_pos_created_at_captain_id_index');
            $table->dropIndexIfExists('orders_branch_id_pos_created_at_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
