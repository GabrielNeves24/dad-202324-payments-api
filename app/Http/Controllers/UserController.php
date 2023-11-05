<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getAllUsers()
    {
        $users = User::all();
        //return User::all();//response()->json(['users' => $users], 200);
        return response()->json(['users' => $users], 200);
    }
    
    public function getUserById($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['user' => $user], 200);
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['user' => $user], 200);
    }

    public function verifyPassword(Request $request)
    {
        $enteredPassword = $request->input('enteredpassword');
        $userType = $request->input('userType');
        $id = $request->input('user');
        if ($userType == 'user') {
            $user = User::where('id', $id)->first();
        } else {
            $user = VCard::where('phone_number', $id)->first();
        }

        // Compare the entered password with the stored hashed password
        if (Hash::check($enteredPassword, $user->password)) {
            // Password is correct
            return response()->json(['message' => 'Password is correct'], 200);
        } else {
            // Password is incorrect
            return response()->json(['message' => 'Password is incorrect'], 400);
        }

    }


    public function store(Request $request)
    {
        // Validate the request
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            // Add validation for custom_options and custom_data if needed
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'custom_options' => $request->custom_options,
            'custom_data' => $request->custom_data,
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        // Find the user
        $user = User::findOrFail($id);

        // Validate the request
        $this->validate($request, [
            'name' => 'string',
            'email' => 'email|unique:users,email,' . $user->id,
            // Add validation for custom_options and custom_data if needed
        ]);

        // Update the user
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'custom_options' => $request->custom_options,
            'custom_data' => $request->custom_data,
        ]);

        return response()->json(['user' => $user], 200);
    }

    public function destroy($id)
    {
        // Find the user
        $user = User::findOrFail($id);

        // Delete the user
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
