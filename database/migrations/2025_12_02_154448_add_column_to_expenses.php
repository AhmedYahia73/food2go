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
        Schema::table('expenses', function (Blueprint $table) {
            // remove FK first
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');

            // add new column
            $table->string("expense")->nullable();
        });

        // now it's safe to drop the table
        Schema::dropIfExists('expense_lists');
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            //
        });
    }
};
