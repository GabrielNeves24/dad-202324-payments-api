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
        if ($user && Hash::check($enteredPassword, $user->password)) {
            return response()->json(['isValid' => true], 200);
        } else {
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

        if ($user && Hash::check($enteredPassword, $user->confirmation_code)) {
            return response()->json(['isValid' => true], 200);
        } else {
            return response()->json(['isValid' => false], 400);
        }

    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);
        if (User::where('email', '=', $validatedData['email'])->exists()) {
            return response()->json(['error' => 'Email já existe'], 401);
        }
         $user = RealUser::create([
             'password' => bcrypt($validatedData['password']),
             'name' => $validatedData['name'],
             'email' => $validatedData['email'],
             'remeber_token' => 'null',
             'email_verified_at' => 'null',
         ]);
         $user->save();
        return response()->json(['message' => 'User criado com sucesso'], 201);

    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
        ]);

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

            $user = RealUser::find($request->id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

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

                $user = RealUser::find($request->id);
                if (!$user) {
                    return response()->json(['error' => 'User not found'], 404);
                }

                $user->password = Hash::make($request->password);
                $user->save();
        
                return response()->json(['message' => 'Password updated successfully']);
            }else{
                return response()->json(['error' => 'Name or Email or Password not found'], 404);
            }
        }
    }

    public function destroy($id)
    {
        $user = RealUser::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
