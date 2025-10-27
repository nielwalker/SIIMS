<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{

     /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    /**
     * Create a folder for the application.
     */
    private function createApplicationFolder($folderPath)
    {
        if (!Storage::exists($folderPath)) {
            return Storage::makeDirectory($folderPath);
        }

        return false; // Folder already exists or failed to create
    }
}
