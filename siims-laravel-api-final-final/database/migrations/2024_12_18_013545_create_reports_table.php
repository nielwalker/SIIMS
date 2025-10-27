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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('application_id')->constrained('applications');
            
            $table->uuid('application_id');
            // Add foreign key constraints
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

            $table->foreignId('report_type_id')->constrained('report_types');
            $table->string('file_path')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
