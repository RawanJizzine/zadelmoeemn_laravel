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
        Schema::table('competitions', function (Blueprint $table) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->string('status')->default('new'); // Adding status column with default value 'active'
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            Schema::table('competitions', function (Blueprint $table) {
                $table->dropColumn('status'); // Removing status column
            });
        });
    }
};
