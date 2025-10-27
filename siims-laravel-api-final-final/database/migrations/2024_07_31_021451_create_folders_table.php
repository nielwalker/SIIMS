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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(
                table: 'users'
            )->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained(
                table: 'folders'
            )->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(
                table: 'users'
            )->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->integer('file_size');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('files');
        Schema::dropIfExists('folders');
    }
};
