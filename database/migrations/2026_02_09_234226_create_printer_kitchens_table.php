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
        Schema::create('printer_kitchens', function (Blueprint $table) {
            $table->id();
            $table->string("print_name");
            $table->string("print_ip");
            $table->boolean("print_status")->default(1);
            $table->enum("print_type", ['usb', 'network']);
            $table->string("print_port");
            $table->foreignId('kitchen_id')->nullable()->constrained('kitchens')->onUpdate('cascade')->onDelete('cascade');
            $table->string("module")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer_kitchens');
    }
};
