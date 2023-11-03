<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\VCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     // Validate the request data
    //     $validatedData = $request->validate([
    //         'phone_number' => 'required|string|regex:/^9[1236]\d{7}$/|unique:users',
    //         'password' => 'required|string|min:8',
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'photo' => 'nullable|image|max:2048',
    //         'confirmation_code' => 'required|digits:4',
    //     ]);

    //     // Create the user
    //     $user = User::create([
    //         'phone_number' => $validatedData['phone_number'],
    //         'password' => bcrypt($validatedData['password']),
    //         'name' => $validatedData['name'],
    //         'email' => $validatedData['email'],
    //         'confirmation_code' => $validatedData['confirmation_code'],
    //         'max_debit' => 5000,
    //     ]);

    //     // Create a new VCard for the user
    //     $user->vcard()->create([
            
    //     ]);

    //     // Upload the photo if it exists
    //     if ($request->hasFile('photo')) {
    //         $photo = $request->file('photo');
    //         $filename = $user->id . '.' . $photo->getClientOriginalExtension();
    //         $photo->storeAs('public/photos', $filename);
    //         $user->photo = $filename;
    //         $user->save();
    //     }

    //     // Issue a token for the user
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     // Return the token as a response
    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'Bearer',
    //     ]);
    // }

    public function register(Request $request)
    {
        if ($request->has('phone_number')) {
            return $this->registerCliente($request);
        } else {
            return $this->registerUser($request);
        }
    }

    public function registerUser(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        // Create the user
         $user = User::create([
             'password' => bcrypt($validatedData['password']),
             'name' => $validatedData['name'],
             'email' => $validatedData['email'],
             'remeber_token' => 'null',
             'email_verified_at' => 'null',
         ]);
         $user->save();


        // Issue a token for the user
        $token = $user->createToken('auth_token')->accessToken;

        // Retunr the data to my Vue3 axios 
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function registerCliente(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'phone_number' => 'required|string|regex:/^9[1236]\d{7}$/|unique:vcards',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'photo' => 'nullable|image|max:2048',
            'confirmation_code' => 'required|digits:4',
        ]);
        // Create a new VCard for the user
        $vCard = Vcard::create([
            'phone_number' => $validatedData['phone_number'],
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'photo_url' => 'image|mimes:jpeg,jpg|max:4096',
            'password' => bcrypt($validatedData['password']),
            'confirmation_code' => bcrypt($validatedData['confirmation_code']),
            'blocked' => 0,
            'balance' => 0,
            'max_debit' => 5000,
        ]);

        // Upload the photo if it exists
        if ($request->hasFile('photo_url')) {
            $picture = $request->file('photo_url');
            $picturePath = $picture->store('fotos', 'public'); // Store in the public storage, under "uploads" folder
            // Save the image URL in the database
            $vCard->photo_url = url('storage/' . $picturePath);
        }

        // Issue a token for the user
        $token = $vCard->createToken('auth_token')->accessToken;
        $vCard->save();
        // Retunr the data to my Vue3 axios 
        return response()->json([
            'vCard' => $vCard,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
        
    public function login(Request $request)
    {
        if ($request->has('phone_number')) {
            return $this->loginByPhoneNumber($request);
        } else {
            return $this->loginByEmail($request);
        }
    }

    private function loginByPhoneNumber(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);
        if($validator->fails()){
            return response()->json(['error' => $validator->errors()->all()]);
        }

        // Attempt to log in the VCard
        if (Auth::guard('vcard')->attempt(['phone_number' => request('phone_number'), 'password' => request('password')])) {
            config(['auth.guards.api.provider' => 'vcard']);

            // $vCard = Auth::guard('web2')->user();
            // $token = $vCard->createToken('auth_token');
            $vcard = Vcard::find(auth()->guard('vcard')->user()->phone_number);
            $success =  $vcard;
            $success['token'] =  $vcard->createToken('auth_token')->accessToken; 
            return response()->json([
                'vcard' => $vcard,
                'access_token' => $success['token'],
                'token_type' => 'Bearer',
            ]);

            // return response()->json([
            //     'vcard' => $vCard,
            //     'access_token' => $token->accessToken,
            //     'token_type' => 'Bearer',
            // ]);
        } else {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    }

    private function loginByEmail(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt to log in the user
        if (!Auth::attempt($validatedData)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Issue a token for the user and retrieve the plain text token
        $token = $user->createToken('auth_token');

        // Return the token as a response
        return response()->json([
            'user' => $user,
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