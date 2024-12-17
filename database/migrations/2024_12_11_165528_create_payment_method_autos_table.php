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
        Schema::create('payment_method_autos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['live', 'test']);
            $table->string('callback');
            $table->string('api_key', 1000);
            $table->string('iframe_id');
            $table->string('integration_id');
            $table->string('Hmac');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_autos');
    }
};
