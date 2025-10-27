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
        Schema::create('endorse_students', function (Blueprint $table) {
            /* $table->foreignId('endorse_req_id')
                ->constrained('endorsement_letter_requests') 
                ->onDelete('cascade');   */

            $table->uuid('endorse_req_id');

            $table->foreign('endorse_req_id')->references('id')->on('endorsement_letter_requests')->onDelete('cascade');

            $table->foreignId('student_id')
                ->constrained('students') // Automatically assumes 'students' table
                ->onDelete('cascade');  // If a student is deleted, also delete their endorsement

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorse_students');
    }
};
