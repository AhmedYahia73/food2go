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
        Schema::create('deal_financial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_id')->nullable()->constrained('finantiol_acountings')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('deal_id')->nullable()->constrained('deal_user')->onUpdate('cascade')->onDelete('set null');
            $table->float("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_financial');
    }
};
