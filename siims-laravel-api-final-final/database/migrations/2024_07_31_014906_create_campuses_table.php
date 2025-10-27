<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {   

        // campuses table
        // * Each campus can have at least one or many colleges
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // colleges table
        // * Each college belongs to a campus
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained(
                table: 'campuses'
            );
            $table->foreignId('dean_id')->nullable()->constrained(
                table: 'users'
            )->nullOnDelete();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('colleges');
        Schema::dropIfExists('campuses');
    }
};
