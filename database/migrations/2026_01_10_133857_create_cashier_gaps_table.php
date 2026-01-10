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
        Schema::create('cashier_gaps', function (Blueprint $table) {
            $table->id();
            $table->float("amount");
            $table->integer("shift");
            $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('cashier_man_id')->nullable()->constrained('cashier_men')->onUpdate('cascade')->onDelete('set null');
            $table->integer("shift")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_gaps');
    }
};
