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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('admin_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('cahier_man_id')->nullable()->constrained('cashier_men')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained('expense_lists')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('financial_account_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('set null');
            $table->float("amount");
            $table->string("note", 1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
