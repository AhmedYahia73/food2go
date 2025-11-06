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
        Schema::create('manufaturing_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufaturing_id')->nullable()->constrained("manufaturings")->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('material_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->float("quantity");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufaturing_recipes');
    }
};
