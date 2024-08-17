<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $timezone = $request->input('timezone'); // Get the timezone from the request

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Update the user's timezone in the database
            if ($timezone) {
                $user->timezone = $timezone;
                $user->save();
            }

            $token = $user->createToken('LaravelApp')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }
}
