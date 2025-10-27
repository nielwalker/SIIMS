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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('status_id')->default(8)->constrained('statuses');
            $table->foreignId('program_id')->constrained('programs');
            $table->foreignId('coordinator_id')->nullable()->constrained('coordinators');

             /**
             * New Attribute: An attribute for referencing the section.
             */
            $table->uuid('section_id')->nullable();
            // $table->foreignId('section_id')->nullable()->constrained('sections');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');


             /**
             * New Attribute: An attribute for referencing the student status.
             */
            $table->foreignId('student_status_id')->default(1)->constrained('student_statuses');

             /**
             * New Attribute (Januray 9, 2025)
             */
            $table->string('linked_in_url')->nullable();
            /**
             * New Attribute (Januray 9, 2025)
             */
            $table->string('github_url')->nullable();

            /**
             * New Attribute.
             */
            $table->longText('about_me')->nullable();

            $table->unique('user_id');
            $table->integer('age')->nullable();
            // $table->boolean('isNotYetApplied')->default(true);
            $table->boolean('isApplied')->default(false);
            $table->boolean('isPending')->default(false);
            $table->date('date_of_birth')->nullable();
            $table->timestamp('last_applied_at')->nullable(); // For tracking last application date
            // New attributes for withdrawal and application block
            $table->boolean('isWithdrawn')->default(false);
            $table->timestamp('withdrawal_date')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamp('blocked_until')->nullable(); // When the student can apply again

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
