<?php

namespace App\Repositories;

interface DocumentSubmissionRepositoryInterface
{
    public function generateStepOneDocumentSubmissions(string $application_id);
}
