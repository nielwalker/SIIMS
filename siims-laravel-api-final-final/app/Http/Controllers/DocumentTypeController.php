<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentTypeRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Models\DocumentType;
use App\Repositories\DocumentTypeRepositoryInterface;
use App\Repositories\LogRepositoryInterface;
use App\Services\DocumentTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class DocumentTypeController extends Controller
{


    // Service
    private $documentTypeService;

    // DocumentTypeV2Controller constructor
    public function __construct(DocumentTypeService $documentTypeService)
    {
        $this->documentTypeService = $documentTypeService;
    }

    /**
     * Summary of restoreDocumentTypeByID: A public function that restore a document type by ID.
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreDocumentTypeByID(string $id): JsonResponse
    {

        // Restore Document Type
        $this->documentTypeService->restoreByID($id);

        // Return 
        return $this->jsonResponse([
            'message' => 'Document Type Restored',
            'type' => 'restore',
        ], 201);
    }

    /**
     * Summary of updateDocumentTypeID: A public function that updates the document type by ID.
     * @param \App\Http\Requests\DocumentTypeRequest $documentTypeRequest
     * @param string $document_type_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDocumentTypeID(DocumentTypeRequest $documentTypeRequest, string $document_type_id): JsonResponse
    {

        // Get validated
        $validated = $documentTypeRequest->validated();

        // Update document type
        $documentType = $this->documentTypeService->updateByID($document_type_id, $validated);

        // Return 
        return $this->jsonResponse(data: ['message' => "Document type is updated.", 'data' => new DocumentTypeResource(resource: $documentType)], status: 201);
    }

    /**
     * Summary of getAllDocumentTypes: A public function that gets all document types
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllDocumentTypes(Request $request): AnonymousResourceCollection
    {
        // Add Filters
        $filters = [
            'status' => $request->query('status'),
            'searchTerm' => $request->query('searchTerm'),
            'perPage' => $request->query('perPage', 5),
            'requestedBy' => $request->query('requestedBy'),
        ];

        // Get all document types
        $documentTypes = $this->documentTypeService->getAll($filters);

        // Transform the paginated data into a resource collection
        $documentTypesResources = DocumentTypeResource::collection(resource: $documentTypes);

        // Return resources
        return $documentTypesResources;
    }

    /**
     * Summary of deleteDocumentTypeByID: A public function that deletes a document type by ID.
     * @param string $document_type_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDocumentTypeByID(String $document_type_id): JsonResponse
    {

        // Delete Document
        $this->documentTypeService->deleteByID($document_type_id);

        // Return
        return $this->jsonResponse(['message' => 'Document type is delete']);
    }

    /**
     * Summary of addDocumentType: A public function that creates a new record of Document Type.
     * @param \App\Http\Requests\DocumentTypeRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addDocumentType(DocumentTypeRequest $request): JsonResponse
    {

        // Get validated data
        $validated = $request->validated();

        // Create
        $documentType = $this->documentTypeService->create($validated);
        

        // Return response with a new Document Type and status 201
        return $this->jsonResponse(data: [
            'data' => new DocumentTypeResource(resource: $documentType),
            'message' => 'A new document is created'
        ], status: 201);
    }
}
