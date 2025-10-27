<?php

namespace App\Repositories;

use App\Models\DocumentType;
use Symfony\Component\HttpFoundation\Response;

class DocumentTypeRepository implements DocumentTypeRepositoryInterface
{

    // Repository variable
    private $logRepositoryInterface;

    // Model
    private $documentType;

    // DocumentTypeRepository constructor
    public function __construct(LogRepositoryInterface $logRepositoryInterface, DocumentType $documentType)
    {
        $this->logRepositoryInterface = $logRepositoryInterface;
        $this->documentType = $documentType;
    }

    /**
     * Summary of isLinkedToRecords: A public function that checks if the records is linked.
     * @param string $id
     * @return bool
     */
    public function isLinkedToRecords(string $id) {

        // Find document
        $documentType = $this->find($id);

        // Return
        return $documentType->documentSubmissions()->exists();

    }

    /**
     * Summary of exists: A public function that returns true if the document type exists.
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool {
        return $this->documentType->withTrashed()->where('id', $id)->exists();
    }

    /**
     * Summary of addLog: A private function that adds new log.
     * @param string $entityID
     * @param string $http_code
     * @param string $actionType
     * @return void
     */
    private function addLog(string $entityID = "", string $http_code, string $actionType): void
    {
        // Create new log
        $this->logRepositoryInterface->create(entityID: $entityID, model: DocumentType::class, http_code: $http_code, actionType: $actionType);
    }
    
    /**
     * Summary of query: Returns initialize query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function query() {
        return $this->documentType->query();
    }

    /**
     * Summary of find: A private function that finds the document type by ID.
     * @param string $id
     * @return DocumentType
     */
    private function find(string $id): DocumentType {
        
        // Find or fail
        $documentType = $this->query()->find($id);

        // Return
        return $documentType;

    }

    /**
     * Summary of update: A public function that finds and update the document type.
     * @param string $id
     * @param array $validated
     * @return DocumentType
     */
    public function update(string $id, array $validated): DocumentType {

        // Find Document type
        $documentType = $this->find(id: $id);

        // Update document type
        $documentType->update(attributes: $validated);
        // Save changes
        $documentType->save();

        // Add log
        $this->addLog(entityID: $documentType->id, http_code: 200, actionType: 'Update');

        // Return
        return $documentType;
        
    }

    /**
     * Summary of restoreByID: A public function that restores a deleted document type by ID.
     * @param string $id
     * @return mixed|DocumentType
     */
    public function restoreByID(string $id): DocumentType
    {
        $documentType = $this->documentType->onlyTrashed()->find($id);
        $documentType->restore();

        // Add Log
        $this->addLog(entityID: $documentType->id, http_code: 200, actionType: 'Restore');

        // Return document type
        return $documentType;
    }

    /**
     * Summary of getAll: A public function that fetches all document types.
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(array $filters)
    {

        // Initialize DocumentType  query
        $query = DocumentType::query();

        if (isset($filters['status']) && $filters['status'] === 'archived') {
            $query->onlyTrashed();
        }

        if (!empty($filters['searchTerm'])) {

            $query->where('name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%');
        }

        // Add Log
        $this->addLog('N/A', 200, 'View');

        // Return paginate
        return $query->paginate($filters['perPage'] ?? 5);
    }


    /**
     * Summary of delete: A public function that deletes a document type by ID.
     * @param string $id
     * @return void
     */
    public function delete(string $id): void {

        // Find document type
        $documentType = $this->find(id: $id);

        // Delete document type
        $documentType->delete();

        // Add Log
        $this->addLog(entityID: $documentType->id, http_code: 200, actionType: 'Delete');

    }

    /**
     * Summary of create: A public function that creates a new document type record.
     * @param array $data
     * @return DocumentType
     */
    public function create(array $data): DocumentType
    {

        // Create new Document Type
        $documentType = DocumentType::create(attributes: $data);

        // Add Log
        $this->addLog(entityID: $documentType->id, http_code: 201, actionType: 'Create');

        // Return
        return $documentType;
    }
}
