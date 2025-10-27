<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeanProfileController extends ProfileController
{

    /**
     * Summary of getProfile: A public function that gets a Profile of Dean
     * @return array
     */
    public function getProfile()
    {

        // Get Dean User Info
        $user = $this->getGeneralInfo()->toArray(request());

        // Find Dean User's info
        $college = College::where('dean_id', $user['id'])->first();

        // Define additional attributes to merge
        $merge_array = [
            "college_name" => $college->name ?? "No College assigned",
        ];

        // Merge additional attributes into the user data
        $merged_user = array_merge($user, $merge_array);

        // Return the merged array
        return $merged_user;
    }

    /**
     * Summary of updateProfile: A public function that updates and return User (Dean) Profile
     * @param \App\Http\Requests\UserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UserRequest $request)
    {
        // Get validated data from the request
        $validated = $request->validated();

        // Update the User Profile
        parent::updateAndReturnUserProfile($validated);

        // Re-fetch the updated user information
        $updatedUser = $this->getGeneralInfo();

        // Pass validated data to the parent method
        return $this->jsonResponse([
            'message' => "Profile updated.",
            'data' => $updatedUser,
        ], 201);
    }
}
