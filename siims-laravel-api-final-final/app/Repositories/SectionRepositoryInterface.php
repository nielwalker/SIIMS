<?php

namespace App\Repositories;

use App\Http\Requests\SectionRequest;

interface SectionRepositoryInterface
{
    public function create(array $validated);
    public function get(array $filters);
    public function queryGet(array $filters);

    public function importAndAddToSection(string $id, SectionRequest $sectionRequest);

    public function getImportedStudentsArray(SectionRequest $sectionRequest);
}
