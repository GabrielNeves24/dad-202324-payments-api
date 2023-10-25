<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json(['users' => $users], 200);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['user' => $user], 200);
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
