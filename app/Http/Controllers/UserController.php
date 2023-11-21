<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function show_me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function getAllUsers()
    {
        $users = User::all();
        //return User::all();//response()->json(['users' => $users], 200);
        return response()->json(['data' => $users], 200);
    }
    
    public function getUserById($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['data' => $user], 200);
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['data' => $user], 200);
    }

    public function verifyPassword(Request $request)
    {
        $enteredPassword = $request->input('enteredPassword');
        $userType = $request->input('userType');
        $id = $request->input('user');

        if ($userType == 'A') {
            $user = User::where('id', $id)->first();
        } else {
            $user = VCard::where('phone_number', $id)->first();
        }

        // Compare the entered password with the stored hashed password
        if ($user && Hash::check($enteredPassword, $user->password)) {
            // Password is correct
            return response()->json(['isValid' => true], 200);
        } else {
            // Password is incorrect
            return response()->json(['isValid' => false], 400);
        }

    }

    public function createUser(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);
        //verificar se não existe um user com o mesmo email
        if (User::where('email', '=', $validatedData['email'])->exists()) {
            return response()->json(['error' => 'Email já existe'], 401);
        }

        // Create the user
         $user = User::create([
             'password' => bcrypt($validatedData['password']),
             'name' => $validatedData['name'],
             'email' => $validatedData['email'],
             'remeber_token' => 'null',
             'email_verified_at' => 'null',
         ]);
         $user->save();
        // Retunr the data to my Vue3 axios 
        return response()->json(['message' => 'User criado com sucesso'], 201);

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
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|string',
        ]);

        // Construct the update data array
        $updateData = [];
        if ($request->filled('name')) {
            $updateData['name'] = $validatedData['name'];
        }

        if ($request->filled('email')) {
            $updateData['email'] = $validatedData['email'];
        }

        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($validatedData['password']);
        }

        // Perform the update using a raw SQL query or Query Builder
        if (!empty($updateData)) {
            \DB::table('users')->where('id', $id)->update($updateData);
        }

        return response()->json(['message' => "User {$user->name} updated successfully"], 200);
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
