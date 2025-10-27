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
        Schema::table('reports', function (Blueprint $table) {
            // Make application_id nullable
            $table->uuid('application_id')->nullable()->change();
        });

        Schema::table('dtr_entries', function (Blueprint $table) {
            // Make application_id nullable
            $table->uuid('application_id')->nullable()->change();
        });

        Schema::table('weekly_entries', function (Blueprint $table) {
            // Make application_id nullable
            $table->uuid('application_id')->nullable()->change();
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Revert application_id to not nullable
            $table->uuid('application_id')->nullable(false)->change();
        });
    }
};
