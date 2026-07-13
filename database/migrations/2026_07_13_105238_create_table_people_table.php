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
        Schema::create('table_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->onUpdate('cascade')->onDelete('cascade');
            $table->integer("count");
            $table->boolean("is_active")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_people');
    }
};
