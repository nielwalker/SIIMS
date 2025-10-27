<?php

namespace App\Repositories;

use App\Models\Student;

interface StudentRepositoryInterface
{


    /** GET */
    public function get(array $filters);

    public function isNotYetApplied();
    public function getLastAppliedAt();
    public function updateToNotYetApplied();
    public function updateToPending();
    public function updateLastAppliedAtToNow();

public function getTotalStudents();

    public function queryAllStudentsFromProgram(String $programID);

    public function findByUserID(string $id);

    public function updateSectionID(Student $student, string $sectionID);
    public function updateCoordinatorID(Student $student, string $coordinatorID);

}
