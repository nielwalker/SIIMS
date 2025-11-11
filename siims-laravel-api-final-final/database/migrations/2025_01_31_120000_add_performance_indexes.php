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
        // Add indexes to weekly_entries table for performance
        Schema::table('weekly_entries', function (Blueprint $table) {
            // Index for coordinator queries (via student_id -> coordinator_id)
            if (!$this->hasIndex('weekly_entries', 'weekly_entries_week_number_index')) {
                $table->index('week_number', 'weekly_entries_week_number_index');
            }
            if (!$this->hasIndex('weekly_entries', 'weekly_entries_student_id_index')) {
                $table->index('student_id', 'weekly_entries_student_id_index');
            }
            // Composite index for common query pattern
            if (!$this->hasIndex('weekly_entries', 'weekly_entries_student_week_index')) {
                $table->index(['student_id', 'week_number'], 'weekly_entries_student_week_index');
            }
        });

        // Add indexes to students table for performance
        Schema::table('students', function (Blueprint $table) {
            if (!$this->hasIndex('students', 'students_coordinator_id_index')) {
                $table->index('coordinator_id', 'students_coordinator_id_index');
            }
            if (!$this->hasIndex('students', 'students_section_id_index')) {
                $table->index('section_id', 'students_section_id_index');
            }
            // Composite index for coordinator + section queries
            if (!$this->hasIndex('students', 'students_coord_section_index')) {
                $table->index(['coordinator_id', 'section_id'], 'students_coord_section_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_entries', function (Blueprint $table) {
            $table->dropIndex('weekly_entries_week_number_index');
            $table->dropIndex('weekly_entries_student_id_index');
            $table->dropIndex('weekly_entries_student_week_index');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_coordinator_id_index');
            $table->dropIndex('students_section_id_index');
            $table->dropIndex('students_coord_section_index');
        });
    }

    /**
     * Check if an index exists
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $doctrineSchemaManager->introspectTable($table);
            return $doctrineTable->hasIndex($indexName);
        } catch (\Exception $e) {
            // If introspection fails, assume index doesn't exist
            return false;
        }
    }
};

