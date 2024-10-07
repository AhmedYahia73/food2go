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
        Schema::create('copuns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('code');
            $table->date('start_date');
            $table->date('expire_date');
            $table->float('min_purchase');
            $table->float('max_discount');
            $table->float('discount');
            $table->enum('discount_type', ['value', 'percentage']);
            $table->boolean('order');
            $table->boolean('status')->default(1);
            $table->integer('limit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copuns');
    }
};
