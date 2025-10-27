<?php

namespace App\Repositories;

use App\Models\Program;

class ProgramRepository implements ProgramRepositoryInterface
{

    // Model variable
    private $program;

    /**
     * Create a new class instance.
     */
    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    /**
     * Summary of getTotalPrograms: A public function that gets the total programs.
     * @return int
     */
    public function getTotalPrograms() {

        // Return total programs
        return $this->program->count();

    }

    /**
     * Summary of getProgramByChairperson: Get program by chairperson.
     * @param string $userID
     * @return TModel|null
     */
    public function getProgramByChairperson(String $userID) {


        return $this->findChairperson($userID);

      
    }

    /**
     * Summary of findChairperson: Find Chairperson in the program.
     * @param string $userID
     * @return TModel|null
     */
    public function findChairperson(String $userID) {

        return $this->program->where('chairperson_id', $userID)->first();

    }
}
