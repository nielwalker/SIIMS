<?php

namespace App\Repositories;

use App\Models\DocumentType;

interface DocumentTypeRepositoryInterface
{
    public function restoreByID(string $id);
    public function getAll(array $filters);

    public function create(array $data);
    public function delete(string $id);
    public function update(string $id, array $validated);

    public function isLinkedToRecords(string $id);
    public function exists(string $id);
}
