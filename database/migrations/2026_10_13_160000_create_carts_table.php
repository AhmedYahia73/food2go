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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_man_id')->nullable()->constrained('cashier_men')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onUpdate('cascade')->onDelete('cascade');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->json('addons')->nullable();
            $table->json('extras')->nullable();
            $table->json('exclude')->nullable();
            $table->json('variation')->nullable();
            $table->enum('type', ['takeaway', 'take_away', 'delivery'])->default('takeaway');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
