<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    //

    public function sendResetLinkEmail(EmailRequest $request) {

        // Get validated
        $validated = $request->validated();

        $user = User::where('email', $validated->email)->first();
        $token = $user->createToken('password_reset')->plainTextToken;

        // Send email with the reset link
        // Mail::to($user->email)->send(new ForgotPasswordMail);

    }
}
