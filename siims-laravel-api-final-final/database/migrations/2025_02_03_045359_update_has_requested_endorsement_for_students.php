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
        // Update students who requested an endorsement letter
        DB::table('students')
            ->join('endorsement_letter_requests', 'students.id', '=', 'endorsement_letter_requests.requested_by_id')
            ->update(['students.has_requested_endorsement' => true]);

        // Update students who are included in any endorsement letter request
        DB::table('students')
            ->join('endorse_students', 'students.id', '=', 'endorse_students.student_id')
            ->update(['students.has_requested_endorsement' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
