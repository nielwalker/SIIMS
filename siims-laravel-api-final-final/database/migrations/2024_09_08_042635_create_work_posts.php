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
        Schema::create('work_posts', function (Blueprint $table) {
            // $table->id();

            $table->uuid('id')->primary();

            $table->foreignId('office_id')->nullable()->constrained(table: 'offices');
            $table->foreignId('work_type_id')->nullable()->constrained(table: 'work_types');
            $table->string('title')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('qualifications')->nullable();
            $table->integer('max_applicants')->nullable()->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('work_duration')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        // Creates a table for work_skill
        // * Each work_posts has one or many skills
        Schema::create('work_skills', function (Blueprint $table) {
            $table->id();

             $table->uuid('work_post_id');
             // Add foreign key constraints
            $table->foreign('work_post_id')->references('id')->on('work_posts')->onDelete('cascade');

            /* $table->foreignId('work_post_id')->constrained(table: 'work_posts'); */
            $table->foreignId('skill_id')->constrained(table: 'skills');
            $table->unique('skill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('work_skills');
        Schema::dropIfExists('work_posts');
    }
};
