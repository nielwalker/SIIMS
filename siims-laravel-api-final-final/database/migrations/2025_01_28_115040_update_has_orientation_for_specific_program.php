<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update has_orientation for a specific program ID
        DB::table('programs')->where('id', 8)->update(['has_orientation' => true]); // Replace 8 with the specific ID
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally revert the update for the specific program ID
        DB::table('programs')->where('id', 8)->update(['has_orientation' => false]); // Replace 1 with the same ID
    }
};
