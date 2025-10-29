<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_entry_requests', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('coordinator_id')->nullable();
            $table->unsignedInteger('week_number');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_entry_requests');
    }
};


