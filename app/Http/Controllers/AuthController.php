<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        // Issue a token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return the token as a response
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
        
    public function login(Request $request)
    {
         // Validate the request data
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ]);

        // Attempt to log in the user
        if (!Auth::attempt($validatedData)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Get the authenticated user
        $user = $request->user();

        // Issue a token for the user and retrieve the plain text token
        $token = $user->createToken('auth_token');

        // Return the token as a response
        return response()->json([
            'user' => Auth::user(),
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
        ]);
    }

    



    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}