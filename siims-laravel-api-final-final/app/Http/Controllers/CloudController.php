<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CloudController extends Controller
{
    /**
     * Summary of storeFile: A public function that stores the file to the cloud storage.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return string
     */
    public function storeFile(Request $request) {

        $file = $request->file('pdf_file');

        // Store the uploaded PDF file in the designated folder
        $pdf_path = $file->store("public/uploads/endorsement_letters", [
           'disk' => 's3',
           'visibility' =>  'public'
        ]);

        // Create a URL to access the uploaded file
        $fileUrl = Storage::disk('s3')->url($pdf_path);

        // Return File URL
        return $fileUrl;

    }


    public function storeFileToCloud($request, String $path, String $fileName) {

        $file = $request->file($fileName);
        
        // Store the file in the S3 within the bucket
        $filePath = $file->store($path, [
            'disk' => 's3',
            'visibility' => 'public',
        ]);

        // Create a URL to access the upload file
        $fileUrl = Storage::disk('s3')->url($filePath);

        // Return File URL
        return $fileUrl;

    }
}
