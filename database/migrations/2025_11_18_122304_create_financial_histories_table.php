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
        Schema::create('financial_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_financial_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('to_financial_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('admin_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->float('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_histories');
    }
};
