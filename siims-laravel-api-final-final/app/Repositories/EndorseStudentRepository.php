<?php

namespace App\Repositories;

use App\Models\EndorseStudent;

class EndorseStudentRepository implements EndorseStudentRepositoryInterface
{

    // Model
    private $endorseStudent;

    /**
     * Create a new class instance.
     */
    public function __construct(EndorseStudent $endorseStudent)
    {
        $this->endorseStudent = $endorseStudent;
    }

    /**
     * Summary of create: A public function that adds new endorsed students.
     * @param array $studentIDs
     * @param string $endorsementLetterRequestID
     * @return void
     */
    public function create(array $studentIDs, string $endorsementLetterRequestID)
    {

        if (!empty($studentIDs)) {
            // Add each student with endorse_req_id
            foreach ($studentIDs as $studentID) {
                $this->endorseStudent->create([
                    "endorse_req_id" => $endorsementLetterRequestID,
                    'student_id' => $studentID['student_id'], // Addd each student ID
                ]);
            }
        }
    }
}
