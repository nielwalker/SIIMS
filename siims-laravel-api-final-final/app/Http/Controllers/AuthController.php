<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\ActionLogger;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{

    // Log Controller
    private $logController;

    // AuthController constructor
    public function __construct(LogController $logController)
    {

        $this->logController = $logController;
    }

    public function resetPassword(Request $request)
    {
        // Validate the request data
        $request->validate([
            'token' => 'required|string', // Validate the token
            'email' => 'required|email', // Ensure the email is provided and valid
            'password' => 'required|string|min:8|confirmed', // Validate password and confirmation
        ]);

        // Get the data from the request
        $token = $request->input('token');
        $email = $request->input('email');
        $password = $request->input('password');

        // Log the email and token for debugging
        // Log::info("Attempting password reset for email: $email with token: $token");

        // Attempt to reset the password using the token
        $status = Password::reset(
            [
                'token' => $token,
                'email' => $email, // The email needs to be passed as well
                'password' => $password,
            ],
            function ($user) use ($password) {
                // Reset the password for the user
                $user->password = bcrypt($password);
                $user->save();
                // Log::info("Password reset successful for user: " . $user->email);
            }
        );

        // Check if password reset was successful
        if ($status === Password::PASSWORD_RESET) {
            // Password reset was successful
            // Log::info("Password reset successful for email: $email");
            return response()->json(['message' => 'Your password has been reset successfully.'], 200);
        } else {
            // Something went wrong
            //Log::error("Password reset failed for email: $email with status: $status");
            return response()->json(['message' => 'Failed to reset password. Please try again.'], 400);
        }
    }
    
    
    public function sendResetLinkEmail(EmailRequest $request)
    {
        // Get validated data from the request
        $validated = $request->validated();

        // Log the validated email data for debugging
        // Log::info('Validated data:', ['email' => $validated['email']]);

        // Send the reset link to the provided email
        $status = Password::sendResetLink(
            ['email' => $validated['email']] // Make sure to pass the correct array format
        );

        // Return the response based on the status
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 200); // Success
        }

        // If there's an error (like invalid email or failure), return an error response
        return response()->json(['message' => __($status)], 400); // Failure
    }

    // Attempts to login the request credentials and generate token
    public function login(LoginRequest $request, ActionLogger $actionLogger)
    {

        // Retrieve the validated input data...
        $validatedCredentials = $request->validated();

        // Check if the user exists and is not soft-deleted
        $user = User::withTrashed()->where('id', $validatedCredentials['id'])->first();

        if ($user && $user->trashed()) {
            // If the account is soft-deleted, return an error response
            return response()->json([
                'message' => 'Your account has been deleted. Please contact support for assistance.'
            ], 403);
        }

        // Attempt the user to login
        if (Auth::attempt($validatedCredentials)) {

            // Get Authenticated User
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Get Token
            $token = $user->createToken('access_token')->plainTextToken;

            // Get roles
            $roles = $user->roles()->pluck('name');

            // Use ActionLogger to log the event
            /* $actionLogger->logAction(
                actionType: 'Login',
                entity: 'None',
                entityId: 0,
                description: "User $user->id attempts to log in the to the system and succeed.",
                status: 'Success',
                httpCode: "200",
            ); */

            // Store the login success log in the database
            $this->logController->addNewLog(
                'Login',
                'N/A',
                'N/A',
                'logged in sucessfully.',
                '200',
            );

            // Return Authenticated User
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
                'roles' => $roles,
            ], 200);
        }

        // Return invalid credential response
        return response()->json([
            'message' => 'The provided credentials do not match our records.'
        ], 401);
    }

    /**
     * Summary of logout: A public function that attempts to log out the authenticated user.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {

        // Revoke the user's current token
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Store the login success log in the database
        // Store the login success log in the database
        $this->logController->addNewLog(
            'Logout',
            'N/A',
            'N/A',
            'logged out sucessfully.',
            '200',
        );

        // Revoke all tokens if you want to invalidate all of them
        $user->tokens()->delete();



        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
