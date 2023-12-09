<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use App\Models\RealUser;
use Illuminate\Support\Facades\Validator;

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

    public function verifyPin(Request $request)
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
        if ($user && Hash::check($enteredPassword, $user->confirmation_code)) {
            // Password is correct
            return response()->json(['isValid' => true], 200);
        } else {
            // Password is incorrect
            return response()->json(['isValid' => false], 400);
        }

    }

    public function create(Request $request)
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
         $user = RealUser::create([
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

    public function update(Request $request)
    {
        if($request->filled('name') && $request->filled('email')){
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'name' => 'string|max:255',
                'email' => 'string|email|max:255',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            // Find the user
            $user = RealUser::find($request->id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            // Update user if data is difrente from original
            if($user->name != $request->name){
                $user->name = $request->name;
            }
            if($user->email != $request->email){
                $user->email = $request->email;
            }
            $user->save();


            return response()->json(['message' => 'User updated successfully']);
        }else{
            if ($request->filled('password')){
                $validator = Validator::make($request->all(), [
                    'id' => 'required|integer',
                    'password' => 'required|string|min:6',
                ]);
                if ($validator->fails()) {
                    return response()->json($validator->errors(), 400);
                }
                // Find the user
                $user = RealUser::find($request->id);
                if (!$user) {
                    return response()->json(['error' => 'User not found'], 404);
                }
                // Update user
                $user->password = Hash::make($request->password);
                $user->save();
        
                return response()->json(['message' => 'Password updated successfully']);
            }else{
                return response()->json(['error' => 'Name or Email or Password not found'], 404);
            }
        }
    }

    // public function updatePassword(Request $request, $id)
    // {
    //         // Validate the request data
    //     $validator = Validator::make($request->all(), [
    //         'password' => 'required|string|min:3',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 400);
    //     }

    //     // Find the user
    //     $user = RealUser::find($id);
        

    //     if (!$user) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }

    //     // Update user password
    //     $user->password = Hash::make($request->password);
    //     $user->save();

    //     return response()->json(['message' => 'Password updated successfully']);
    // }



    public function destroy($id)
    {
        // Find the user
        $user = User::findOrFail($id);

        // Delete the user
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }


}
