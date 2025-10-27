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
        Schema::create('applications', function (Blueprint $table) {
            // $table->id()->primary();
            $table->uuid('id')->primary();

            $table->uuid('work_post_id');

            $table->foreign('work_post_id')->references('id')->on('work_posts');

            // $table->foreignId('work_post_id')->constrained(table: 'work_posts');
            $table->foreignId('student_id')->constrained(table: 'students');
            $table->foreignId('status_type_id')->default(1) ->constrained(table: 'statuses');

            /**
             * New Attribute: An attribute for referencing the application status.
             */
            $table->foreignId('application_status_id')->default(1)->constrained('application_statuses');

            $table->integer('step')->default(1)->nullable();
            $table->text('remarks')->default("");
            $table->timestamp('applied_date')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
