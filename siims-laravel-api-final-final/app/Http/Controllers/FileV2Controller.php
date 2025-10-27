<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileV2Controller extends Controller
{


    /**
     * Summary of store: A public function that stores a file to the cloud and returns the URL. 
     * @param \Illuminate\Http\Request $request
     * @param string $key
     * @param string $path
     * @return string
     */
    public function storeToCloud(Request $request, String $key, String $path, ) {

        // Store the file in the s3 
        $filePath = $request->file($key)->store("public/uploads/", [
            'disk' => 's3',
            'visibility' => 'public',
        ]);

        // Create a URL
        $fileUrl = Storage::disk('s3')->url($filePath);

        // Return File URL
        return $fileUrl;

    }

}
