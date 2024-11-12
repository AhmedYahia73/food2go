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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->datetime('date');
            $table->foreignId('user_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->float('amount');
            $table->enum('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 
            'delivered', 'returned', 'faild_to_deliver', 'canceled', 'scheduled']);
            $table->string('order_type');
            $table->string('payment_status')->nullable();
            $table->float('total_tax')->default(0);
            $table->float('total_discount')->default(0);
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
