<?php

namespace App\Services;

use App\Repositories\DocumentTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class DocumentTypeService
{

    // Repository
    private $documentTypeRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(DocumentTypeRepositoryInterface $documentTypeRepositoryInterface)
    {
        $this->documentTypeRepositoryInterface = $documentTypeRepositoryInterface;
    }

    /**
     * Summary of checkIfExist: A public function that checks if the document type exist,
     * @param string $id
     * @return void
     */
    private function checkIfExist(string $id)
    {

        // Check if document type does not exist.
        if (!$this->documentTypeRepositoryInterface->exists($id)) {
            abort(Response::HTTP_NOT_FOUND, 'Document type not found.');
        }
    }


    /**
     * Summary of create: Create new document type.
     * @param array $validated
     * @return mixed
     */
    public function create(array $validated)
    {

        // Return
        return $this->documentTypeRepositoryInterface->create($validated);
    }


    /**
     * Summary of restoreByID: Restores the soft deleted document type.
     * @param string $id
     * @return mixed
     */
    public function restoreByID(string $id)
    {

        // Check if exist
        $this->checkIfExist($id);

        // Restore
        return $this->documentTypeRepositoryInterface->restoreByID($id);
    }

    /**
     * Summary of getAll: Get all document types.
     * @param array $filters
     * @return mixed
     */
    public function getAll(array $filters)
    {
        return $this->documentTypeRepositoryInterface->getAll($filters);
    }

    /**
     * Summary of updateByID: Updates the document type by ID.
     * @param string $id
     * @param array $validated
     * @return mixed
     */
    public function updateByID(string $id, array $validated)
    {

        // Check if exist
        $this->checkIfExist($id);

        return $this->documentTypeRepositoryInterface->update($id, $validated);
    }

    /**
     * Summary of delete: A public function checks conditions before deleting the document type by ID.
     * @param string $id
     * @return void
     */
    public function deleteByID(string $id)
    {

        // Check if the document type exist
        $this->checkIfExist($id);

        // Check if the record is linked
        if ($this->documentTypeRepositoryInterface->isLinkedToRecords($id)) {
            abort(Response::HTTP_BAD_REQUEST, 'This document type is linked to other records.');
        }

        // Create new document type
        $this->documentTypeRepositoryInterface->delete($id);
    }
}
