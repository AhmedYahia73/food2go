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
        Schema::create('captain_printers', function (Blueprint $table) {
            $table->string("print_name");
            $table->string("print_port");
            $table->string("print_ip");
            $table->string("print_type");
            $table->foreignId('captain_order_id')->nullable()->constrained('captain_orders')->onUpdate('cascade')->onDelete('cascade');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_fees', function (Blueprint $table) {
            //
        });
    }
};
