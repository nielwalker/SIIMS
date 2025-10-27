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
        Schema::create('work_post_skills', function (Blueprint $table) {
            // $table->foreignId('work_post_id')->constrained('work_posts');

            $table->uuid('work_post_id');
            // Add foreign key constraints
            $table->foreign('work_post_id')->references('id')->on('work_posts')->onDelete('cascade');

            $table->foreignId('skill_id')->constrained('skills');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_post_skills');
    }
};
