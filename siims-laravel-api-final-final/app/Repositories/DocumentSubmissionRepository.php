<?php

namespace App\Repositories;

use App\Models\DocumentSubmission;

class DocumentSubmissionRepository implements DocumentSubmissionRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Summary of generateStepOneDocumentSubmissions: A public function that generates a step one document submissions.
     * @param string $application_id
     * @return void
     */
    public function generateStepOneDocumentSubmissions(string $application_id) {

        $documents = [
            [
                "application_id" => $application_id,
                "doc_type_id" => 13,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Certificate of Orientation",
            ],
            [
                "application_id" => $application_id,
                "doc_type_id" => 9,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Application Letter",
            ],
            [
                "application_id" => $application_id,
                "doc_type_id" => 3,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Resume",
            ],
            [
                "application_id" => $application_id,
                "doc_type_id" => 2,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Endorsement Letter",
            ],
        ];

        foreach ($documents as $document) {
            DocumentSubmission::create($document);
        }

    }

}
