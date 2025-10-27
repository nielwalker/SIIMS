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
        Schema::create('weekly_entries', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('application_id')->constrained(table: 'applications');
            
            $table->uuid('application_id');
            // Add foreign key constraints
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

            $table->integer('week_number')->nullabe();
            $table->date('start_date')->nullabe();
            $table->date('end_date')->nullabe();
            $table->text('tasks')->nullable();
            $table->text('learnings')->nullable();
            $table->integer("no_of_hours")->nullable();
          
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_entries');
    }
};
