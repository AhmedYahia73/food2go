<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order Carts Table Indexes
        Schema::table('order_carts', function (Blueprint $table) {
            if (!$this->indexExists('order_carts', 'order_carts_table_id_index')) {
                $table->index('table_id', 'order_carts_table_id_index');
            }
            if (!$this->indexExists('order_carts', 'order_carts_captain_id_index')) {
                $table->index('captain_id', 'order_carts_captain_id_index');
            }
            if (!$this->indexExists('order_carts', 'order_carts_user_id_index')) {
                $table->index('user_id', 'order_carts_user_id_index');
            }
        });

        // Order Details Table Indexes
        Schema::table('order_details', function (Blueprint $table) {
            if (!$this->indexExists('order_details', 'order_details_product_id_index')) {
                $table->index('product_id', 'order_details_product_id_index');
            }
        });

        // Product Reviews Table Indexes
        if (Schema::hasTable('product_reviews')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                if (!$this->indexExists('product_reviews', 'product_reviews_product_id_index')) {
                    $table->index('product_id', 'product_reviews_product_id_index');
                }
                if (!$this->indexExists('product_reviews', 'product_reviews_user_id_index')) {
                    $table->index('user_id', 'product_reviews_user_id_index');
                }
            });
        }
        
        // Orders Table additional foreign keys
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'orders_captain_id_index')) {
                $table->index('captain_id', 'orders_captain_id_index');
            }
            if (!$this->indexExists('orders', 'orders_delivery_id_index')) {
                $table->index('delivery_id', 'orders_delivery_id_index');
            }
            if (!$this->indexExists('orders', 'orders_branch_id_index')) {
                $table->index('branch_id', 'orders_branch_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_carts', function (Blueprint $table) {
            $table->dropIndexIfExists('order_carts_table_id_index');
            $table->dropIndexIfExists('order_carts_captain_id_index');
            $table->dropIndexIfExists('order_carts_user_id_index');
        });
        
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndexIfExists('order_details_product_id_index');
        });
        
        if (Schema::hasTable('product_reviews')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropIndexIfExists('product_reviews_product_id_index');
                $table->dropIndexIfExists('product_reviews_user_id_index');
            });
        }
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndexIfExists('orders_captain_id_index');
            $table->dropIndexIfExists('orders_delivery_id_index');
            $table->dropIndexIfExists('orders_branch_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
