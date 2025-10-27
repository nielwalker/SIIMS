<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class ProfileController extends Controller
{

    /**
     * Summary of getGeneralInfo: A protected function that gets all basic info of the User
     * @return UserResource
     */
    protected function getGeneralInfo(): UserResource
    {

        // Get authenticated user
        $user = Auth::user();

        return new UserResource($user);
    }

    /**
     * Summary of updateProfile: A public function that updates and returns the User Profile
     * @param array $validated
     * @return void
     */
    public function updateAndReturnUserProfile(array $validated)
    {

        // Get authenticated user
        $user = Auth::user();

        // Update and Save changes from User
        $user->update($validated);
        $user->save();
    }

    /**
     * Abstract Functions
     */
    abstract protected function getProfile();
}
