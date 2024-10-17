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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['first_order', 'normal']);
            $table->string('title');
            $table->string('code');
            $table->date('start_date');
            $table->date('expire_date');
            $table->float('min_purchase')->default(0);
            $table->boolean('max_discount_status');
            $table->float('max_discount')->nullable();
            $table->enum('product', ['all', 'selected']);
            $table->enum('number_usage_status', ['fixed', 'unlimited']);
            $table->integer('number_usage')->nullable();
            $table->enum('number_usage_user_status', ['fixed', 'unlimited']);
            $table->integer('number_usage_user')->nullable();
            $table->enum('discount_type', ['value', 'percentage']);
            $table->float('discount');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
