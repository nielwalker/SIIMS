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
        Schema::create('endorsement_letter_requests', function (Blueprint $table) {
            // $table->id()->primary();
            $table->uuid('id')->primary();
            // $table->foreignId('work_post_id')->constrained('work_posts');
            
            // $table->foreignId('application_id')->constrained('applications');
            $table->uuid('application_id')->nullable();
            // Add foreign key constraints
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

            // $table->foreignId('work_post_id')->constrained('work_posts');
            // $table->foreignId('company_id')->constrained('company_id');

            $table->uuid('work_post_id')->nullable();
            // Add foreign key constraints
            $table->foreign('work_post_id')->references('id')->on('work_posts');

            $table->enum('type', ['system', 'walk-in'])->default('system');

            /**
             * 
             * This columns are used only for walk ins requests
             * 
             */
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_position')->nullable();
            // $table->string('job_type')->nullable();
       
            $table->foreignId('requested_by_id')->nullable()->constrained('students', 'user_id');
            $table->foreignId('status_id')->default(1)->constrained('statuses');

            // Added attribute (12/15/2024):
            // $table->foreignId('endorsement_letter_request_status_id')->nullable()->constrained('endorsement_letter_request_statuses');

            // Explicitly define a shorter constraint name for the foreign key
            $table->foreignId('endorsement_letter_request_status_id')
                ->default(1)// Pending
                ->constrained('endorsement_letter_request_statuses', 'id', 'fk_ender_status');

            $table->text('description')->nullable();
            $table->string('endorsement_file')->nullable(); // Column for storing the PDF file path
            $table->string('remarks')->nullable();
            $table->date('requested_at')->default(now());
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_letter_requests');
    }
};
