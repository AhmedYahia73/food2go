<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdToChangeLogsTable extends Migration
{
    public function up()
    {
        Schema::table('change_logs', function (Blueprint $table) {
            $table->string('client_id')->nullable()->after('op')->index();
        });
    }

    public function down()
    {
        Schema::table('change_logs', function (Blueprint $table) {
            $table->dropColumn('client_id');
        });
    }
}
