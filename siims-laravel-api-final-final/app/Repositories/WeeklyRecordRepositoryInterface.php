<?php

namespace App\Repositories;

interface WeeklyRecordRepositoryInterface
{
    
    /**
     * 
     * CREATE
     * 
     * 
     */
    public function create(array $validated =[]);

    /**
     * 
     * 
     * GET
     * 
     */
    public function getByStudent(string $student_id = "");
    public function getByCoordinator(string $coordinator_id = "");
    public function get(array $filters = []);
    public function find(string $id = "");

    /**
     * 
     * 
     * UPDATE 
     * 
     * 
     */
    public function update(string $id = "", array $validated = []);

    /**
     * 
     * 
     * DELETE
     * 
     * 
     */
    public function delete(string $id = "");
}
