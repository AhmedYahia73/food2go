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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('material_categories')->onUpdate('cascade')->onDelete('set null');
            $table->string("name");
            $table->string("description", 1000)->nullable();
            $table->boolean("status")->default(1);
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
