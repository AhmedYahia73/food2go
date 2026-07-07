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
        Schema::create('category_banners', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('category_id')->nullable()->constrained('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('banner_id')->nullable()->constrained('banners')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('category_banners');
    }
};
