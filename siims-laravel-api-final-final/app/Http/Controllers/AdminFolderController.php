<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminFolderRequest;
use App\Models\Folder;
use App\Services\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminFolderController extends Controller
{
    // A public function that creates a new folder
    public function addNewFolder(AdminFolderRequest $request, ActionLogger $actionLogger) {
        // Get authenticated user
        $user = Auth::user();
        // Get the validated data from the request
        $validated = $request->validated();
        $folderName = $validated['name'];

        // Define the folder path in the storage
        $folderPath = "public/{$user->id}/{$folderName}";
        
        // Check if the folder already exists
        if (Storage::exists($folderPath)) {
            return response()->json([
                'message' => 'Folder already exists.'
            ], 409); // HTTP Conflict
        }

        // Create the folder in the storage
        Storage::makeDirectory($folderPath);

        // Store the folder path in the database
        $folder = Folder::create([
            "user_id" => $user['id'],
            "name" => $folderName,
            "path" => $folderPath,
        ]);
        
        // Create a folder in the storage in path public/user_id/namedFolder

        // Store the path in the folder in the database

    }
}
