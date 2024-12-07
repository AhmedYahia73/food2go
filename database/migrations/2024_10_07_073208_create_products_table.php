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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('item_type', ['online', 'offline', 'all']);
            $table->enum('stock_type', ['daily', 'unlimited', 'fixed']);
            $table->integer('number')->nullable(); // when stock_type => [daily, fixed]
            $table->float('price');
            $table->boolean('product_time_status');
            $table->time('from')->nullable();
            $table->time('to')->nullable();
            $table->foreignId('discount_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('tax_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->boolean('status')->default(1);
            $table->boolean('recommended')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
