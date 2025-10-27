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

        Schema::create('document_submissions', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('application_id')->constrained(table: 'applications', column: 'id');

            $table->uuid('application_id');
            // Add foreign key constraints
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

            $table->foreignId('document_status_id')->default(1)->constrained(table: 'document_statuses');// * Added at December 28, 2024.
            $table->foreignId('doc_type_id')->constrained(table: 'document_types');
          
            $table->foreignId('doc_status_id')->constrained(table: 'statuses');

           
            // $table->foreignId('status_id')->nullable()->constrained('document_statuses');

            $table->string('name')->default("Document");
            $table->string('file_path')->nullable();
            $table->string('remarks')->nullable();
            // $table->integer('file_size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_steps');
        Schema::dropIfExists('document_submissions');
    }
};
