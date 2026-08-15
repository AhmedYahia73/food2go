<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChangeLogsTable extends Migration
{
    public function up()
    {
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('record_id');
            $table->string('op');
            $table->string('client_id')->nullable();
            $table->json('old_payload')->nullable();
            $table->json('new_payload')->nullable();
            $table->timestamps();
            
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('change_logs');
    }
}
