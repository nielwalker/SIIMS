<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileV2Controller extends Controller
{

    // Services
    private $profileService;

    public function __construct(ProfileService $profileService) {
        $this->profileService = $profileService;
    }

    /**
     * Summary of get: A public function that gets the user.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request) {

        // Filters
        $filters = [
            'status' => $request->query('status'),
            'requestedBy' => $request->query('requestedBy')
        ];

        // Get Profile
        $profile = $this->profileService->get($filters);
        

        // Return
        return $this->jsonResponse($profile, 201);

    }

}
