<?php

namespace App\Repositories;

interface DailyRecordRepositoryInterface
{
    
    // Create
    public function create(array $validated);

    // GET
    public function get();

    public function find(string $id);

    public function getTotal(string $user_id);

    // DELETE
    public function delete(string $id);

    // UDPATE
    public function update(array $validated = [], string $id);
}
