<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // تم إضافة هذا السطر لاستخدام DB::raw

return new class extends Migration
{
    public function up(): void
    {
        // orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'orders_user_id_order_status_index')) {
                $table->index(['user_id', 'order_status'], 'orders_user_id_order_status_index');
            }
            if (!$this->indexExists('orders', 'orders_branch_id_order_status_index')) {
                $table->index(['branch_id', 'order_status'], 'orders_branch_id_order_status_index');
            }
            if (!$this->indexExists('orders', 'orders_status_index')) {
                $table->index('status', 'orders_status_index');
            }
            if (!$this->indexExists('orders', 'orders_transaction_id_index')) {
                $table->index('transaction_id', 'orders_transaction_id_index');
            }
            if (!$this->indexExists('orders', 'orders_created_at_index')) {
                $table->index('created_at', 'orders_created_at_index');
            }
        });

        // translation_tbls table indexes
        Schema::table('translation_tbls', function (Blueprint $table) {
            if (!$this->indexExists('translation_tbls', 'translations_key_locale_index')) {
                // تم تعديل هذا السطر لتحديد طول الفهرس وتفادي مشكلة الـ 3072 بايت
                $table->index([
                    DB::raw('`key`(250)'), 
                    DB::raw('`locale`(10)')
                ], 'translations_key_locale_index');
            }
        });

        // branch_offs table indexes
        Schema::table('branch_offs', function (Blueprint $table) {
            if (!$this->indexExists('branch_offs', 'branch_offs_branch_id_index')) {
                $table->index('branch_id', 'branch_offs_branch_id_index');
            }
        });

        // order_details table indexes
        Schema::table('order_details', function (Blueprint $table) {
            if (!$this->indexExists('order_details', 'order_details_order_id_index')) {
                $table->index('order_id', 'order_details_order_id_index');
            }
        });

        // products table indexes
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_status_item_type_index')) {
                $table->index(['status', 'item_type'], 'products_status_item_type_index');
            }
            if (!$this->indexExists('products', 'products_category_id_index')) {
                $table->index('category_id', 'products_category_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_user_id_order_status_index');
            $table->dropIndexIfExists('orders_branch_id_order_status_index');
            $table->dropIndexIfExists('orders_status_index');
            $table->dropIndexIfExists('orders_transaction_id_index');
            $table->dropIndexIfExists('orders_created_at_index');
        });
        Schema::table('translation_tbls', function (Blueprint $table) {
            $table->dropIndexIfExists('translations_key_locale_index');
        });
        Schema::table('branch_offs', function (Blueprint $table) {
            $table->dropIndexIfExists('branch_offs_branch_id_index');
        });
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndexIfExists('order_details_order_id_index');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndexIfExists('products_status_item_type_index');
            $table->dropIndexIfExists('products_category_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};