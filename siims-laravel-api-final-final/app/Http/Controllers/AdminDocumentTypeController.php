<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminDocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class AdminDocumentTypeController extends Controller
{

    /**
     * A public function that deletes the document type record by ID
     * @param string $document_type_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deleteDocumentTypeById(string $document_type_id)
    {
        // Find Document Type by document_type_id
        $documentType = DocumentType::find($document_type_id);

        // Check if document type exist
        if (!$documentType) {
            return response()->json(['message' => 'Document Type not found.'], 404);
        }

        // Delete document type
        $documentType->delete();

        // Return document types with status 201
        return response()->json(['message' => 'Document type is deleted.'], 201);
    }

    /**
     * A public function that updates a document type record by ID
     * @param \App\Http\Requests\AdminDocumentTypeRequest $request
     * @param string $document_type_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateDocumentTypeById(AdminDocumentTypeRequest $request, string $document_type_id)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find Document Type by document_type_id
        $documentType = DocumentType::find($document_type_id);
        // Check if document type exist
        if (!$documentType) {
            return response()->json(['message' => 'Document Type not found.'], 404);
        }

        // Update the document type
        $documentType->name = $validatedCredentials['name'];
        $documentType->save();

        // Get document record and transform
        $transformedDocumentTypes = $this->transform(DocumentType::find($documentType['id']));

        // Return document types with status 201
        return response()->json(['message' => 'A new document type is updated.', 'data' => $transformedDocumentTypes], 201);
    }

    /**
     * A public function that adds a new record of document type
     * @param \App\Http\Requests\AdminDocumentTypeRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewDocumentType(AdminDocumentTypeRequest $request)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Create new document
        $documentType = DocumentType::create($validatedCredentials);
        // Check if document type is created
        if (!$documentType) {
            return response()->json(['message' => 'Unable to create new document type.'], 400);
        }

        // Get document record and transform
        $transformedDocumentTypes = $this->transform(DocumentType::find($documentType['id']));

        // Return document types with status 201
        return response()->json(['message' => 'A new document type is created.', 'data' => $transformedDocumentTypes], 201);
    }

    /**
     * A public function that gets all the document types records
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllDocumentTypes()
    {

        // Get all document types
        $documentTypes = DocumentType::all();
        // Check if document types exist
        if (!$documentTypes) {
            return response()->json(['message' => 'Document Types not found.'], 404);
        }

        // Transform
        $transformedDocumentTypes = $documentTypes->map(function ($documentType) {
            return $this->transform($documentType);
        });

        // Return document types
        return response()->json($transformedDocumentTypes, 200);
    }

    /**
     * A private function that transformes the attributes for necessary requirements
     * @param mixed $documentType
     * @return array
     */
    private function transform($documentType)
    {
        return [
            "id" => $documentType['id'],
            "name" => $documentType['name'],
            "created_at" => $this->formatDate($documentType['created_at']),
            "updated_at" => $documentType['updated_at'] ? $this->formatDate($documentType['updated_at']) : "",
            "deleted_at" => $documentType['deleted_at'] ? $this->formatDate($documentType['deleted_at']) : "",
        ];
    }
}
