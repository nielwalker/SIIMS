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
        Schema::create('dtr_entries', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('application_id')->constrained(table: 'applications');

            $table->uuid('application_id');
            // Add foreign key constraints
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

             /**
             * New Attribute: An attribute for referencing the document status.
             */
            $table->foreignId('status_id')->nullable()->constrained(table: 'time_record_statuses');


            $table->date('date')->nullable();
            $table->string('time_in')->nullable();
            $table->string('time_out')->nullable();
            $table->integer('hours_received')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_entries');
    }
};
