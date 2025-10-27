<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\DocumentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{   
    /**
     * Summary of storeFile: A public function that stores the file to the cloud storage.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return string
     */
    public function storeFile(Request $request, String $application_id) {

        $file = $request->file('file');

        // Store the file in the S3 'applications' folder within the bucket
        $filePath = $file->store("public/uploads/applications/{$application_id}", [
            'disk' => 's3',
            'visibility' => 'public',
        ]);

        // Create a URL to access the uploaded file
        $fileUrl = Storage::disk('s3')->url($filePath);
        // $fileUrl = Storage::disk('s3')->setVisibility($filePath, 'public');

        // Return File URL
        return $fileUrl;

    }

    // Upload a file and associate it with an application
    public function upload(Request $request, String $application_id)
    {
        // Validate that the request contains the 'file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'file' => 'required|mimes:pdf|max:10240', // max size 10MB
        ]);

        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        if (!$request->file('file')->isValid()) {
            return response()->json(['message' => 'Uploaded file is invalid.'], 400);
        }

        // Find the application
        $application = Application::find($application_id);
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        // Create a URL to access the uploaded file
        $fileUrl = $this->storeFile($file, $application_id);

        // Create a new document submission entry in the database
        $document = DocumentSubmission::create([
            "application_id" => $application_id, // Ensure the application ID is linked
            "doc_status_id" => 2, // Approved
            "doc_type_id" => 5, // Acceptance Letter document type
            "name" => "Acceptance Letter",
            "file_path" => $fileUrl, // Store the URL of the file
            "remarks" => "",
        ]);

        return response()->json(['message' => 'File uploaded successfully', 'fileUrl' => $fileUrl, 'data' => $document]);
    }

    // Handle generic file upload (if needed)
    public function FileUpload(Request $request)
    {
        // Upload a file to the S3 'uploads' folder
        $uploaded_files = $request->file->store('uploads/', 's3');
        return response()->json(["result" => $uploaded_files]);
    }
}
