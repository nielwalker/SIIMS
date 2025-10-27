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
        // programs table
        // * Each program belongs to an
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->nullable()->constrained(
                table: 'colleges'
            );
            $table->foreignId('chairperson_id')->nullable()->constrained(
                table: 'users'
            );
          
            $table->string('name')->nullable();
            $table->integer('max_internships')->nullable()->default(0);
         
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
