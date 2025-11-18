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
        Schema::create('preparation_men', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("password");
            $table->foreignId('branch_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean("status")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preparation_men');
    }
};
