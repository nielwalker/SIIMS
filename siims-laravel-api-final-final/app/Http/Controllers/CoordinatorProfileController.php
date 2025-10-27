<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoordinatorProfileRequest;
use App\Models\Coordinator;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinatorProfileController extends Controller
{   

    public function updateProfile(CoordinatorProfileRequest $request) {

        // Get validated credentials
        $validated = $request->validated();

        // Get auth user
        $auth = Auth::user();

        // Get user
        $user = User::find($auth->id);

        // Update coordinator details
        $user->first_name = $validated['first_name'];
        $user->middle_name = $validated['middle_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->gender = $validated['gender'];

        // Save changes
        $user->save();

        // Return response 201
        return response()->json(['message' => 'Profile updated.'], 201);

    }

    /**
     * Summary of transform: A private function that transforms coordinator's attributes
     * @param mixed $coordinator
     * @return array
     */
    private function transform($coordinator) {
        return [
            "id" => $coordinator->id,
            "first_name" => $coordinator->user->first_name,
            "middle_name" => $coordinator->user->middle_name,
            "last_name" => $coordinator->user->last_name,
            "email" => $coordinator->user->email,
            "gender" => $coordinator->user->gender,
        ];
    }

    /**
     * Summary of getProfile: A public function that gets the profile of coordinator 
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getProfile() {

        // Get auth
        $user = Auth::user();

        // Get coordinator detail
        $coordinator = Coordinator::with(['program', 'user'])->find($user->id);
        // Check if coordinator does exist
        if(!$coordinator) {
            return response()->json(['message' => 'Coordinator not found.'], 404);
        }

        // Transform Coordinator's Attributes
        $transformedCoordinator = $this->transform($coordinator);

        return response()->json($transformedCoordinator, 200);;

    }
}
