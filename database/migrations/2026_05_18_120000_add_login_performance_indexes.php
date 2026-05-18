<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users Table Indexes
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
            if (!$this->indexExists('users', 'users_phone_index')) {
                $table->index('phone', 'users_phone_index');
            }
        });

        // Admins Table Indexes
        Schema::table('admins', function (Blueprint $table) {
            if (!$this->indexExists('admins', 'admins_email_index')) {
                $table->index('email', 'admins_email_index');
            }
            if (!$this->indexExists('admins', 'admins_phone_index')) {
                $table->index('phone', 'admins_phone_index');
            }
        });

        // Deliveries Table Indexes
        Schema::table('deliveries', function (Blueprint $table) {
            if (!$this->indexExists('deliveries', 'deliveries_email_index')) {
                $table->index('email', 'deliveries_email_index');
            }
            if (!$this->indexExists('deliveries', 'deliveries_phone_index')) {
                $table->index('phone', 'deliveries_phone_index');
            }
        });

        // Cashiers Table Indexes
        Schema::table('cashier_men', function (Blueprint $table) {
            if (!$this->indexExists('cashier_men', 'cashier_men_user_name_index')) {
                $table->index('user_name', 'cashier_men_user_name_index');
            }
        });

        // Captain Orders Table Indexes
        Schema::table('captain_orders', function (Blueprint $table) {
            if (!$this->indexExists('captain_orders', 'captain_orders_user_name_index')) {
                $table->index('user_name', 'captain_orders_user_name_index');
            }
            if (!$this->indexExists('captain_orders', 'captain_orders_phone_index')) {
                $table->index('phone', 'captain_orders_phone_index');
            }
        });

        // Waiters Table Indexes
        Schema::table('waiters', function (Blueprint $table) {
            if (!$this->indexExists('waiters', 'waiters_user_name_index')) {
                $table->index('user_name', 'waiters_user_name_index');
            }
        });

        // Kitchens Table Indexes
        Schema::table('kitchens', function (Blueprint $table) {
            if (!$this->indexExists('kitchens', 'kitchens_name_index')) {
                $table->index('name', 'kitchens_name_index');
            }
        });

        // Preparation Men Table Indexes
        Schema::table('preparation_men', function (Blueprint $table) {
            if (!$this->indexExists('preparation_men', 'preparation_men_name_index')) {
                $table->index('name', 'preparation_men_name_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('users_email_index');
            $table->dropIndexIfExists('users_phone_index');
        });
        Schema::table('admins', function (Blueprint $table) {
            $table->dropIndexIfExists('admins_email_index');
            $table->dropIndexIfExists('admins_phone_index');
        });
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndexIfExists('deliveries_email_index');
            $table->dropIndexIfExists('deliveries_phone_index');
        });
        Schema::table('cashier_men', function (Blueprint $table) {
            $table->dropIndexIfExists('cashier_men_user_name_index');
        });
        Schema::table('captain_orders', function (Blueprint $table) {
            $table->dropIndexIfExists('captain_orders_user_name_index');
            $table->dropIndexIfExists('captain_orders_phone_index');
        });
        Schema::table('waiters', function (Blueprint $table) {
            $table->dropIndexIfExists('waiters_user_name_index');
        });
        Schema::table('kitchens', function (Blueprint $table) {
            $table->dropIndexIfExists('kitchens_name_index');
        });
        Schema::table('preparation_men', function (Blueprint $table) {
            $table->dropIndexIfExists('preparation_men_name_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
