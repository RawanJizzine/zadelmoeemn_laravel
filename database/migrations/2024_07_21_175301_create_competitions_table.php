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
        Schema::create('competitions', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name'); // Name of the competition
            $table->string('type'); // Type of the competition
            $table->string('instagram')->nullable(); // Instagram handle (nullable)
            $table->date('start_date'); // Start date of the competition
            $table->date('end_date'); // End date of the competition
            $table->string('competition_number'); // Unique competition number
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
