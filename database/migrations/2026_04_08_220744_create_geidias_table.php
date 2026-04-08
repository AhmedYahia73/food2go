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
        Schema::create('geidias', function (Blueprint $table) {
            $table->id(); 
            $table->string("geidea_public_key");
            $table->string("api_password");
            $table->string("environment");
            $table->foreignId('payment_method_id')->nullable()->constrained('materials')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geidias');
    }
};
