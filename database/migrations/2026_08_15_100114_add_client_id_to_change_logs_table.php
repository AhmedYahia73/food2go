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
        if (!Schema::hasColumn('change_logs', 'client_id')) {
            Schema::table('change_logs', function (Blueprint $table) {
                $table->string('client_id')->nullable()->after('op');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('change_logs', 'client_id')) {
            Schema::table('change_logs', function (Blueprint $table) {
                $table->dropColumn('client_id');
            });
        }
    }
};
