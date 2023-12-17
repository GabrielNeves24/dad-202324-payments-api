<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\VCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\DefaultCategory;
use App\Models\Category;
use Exception;

class AuthController extends Controller
{
    private function passportAuthenticationData($username, $password)
    {
        return [
            'grant_type' => 'password',
            'client_id' => env('PASSPORT_CLIENT_ID'),
            'client_secret' => env('PASSPORT_CLIENT_SECRET'),
            'username' => $username,
            'password' => $password,
            'scope' => ''
        ];
    }

    public function login(Request $request)
    {
        $vCard = User::where('username', '=', $request->username)->first();
        if($vCard->blocked == 1){
            return response()->json(['error' => 'Utilizador Bloqueado! Contactar administração'], 403);
        }
        if($vCard->deleted_at != null){
            return response()->json(['error' => 'VCard Eliminado!'], 403);
        }
        try {
            request()->request->add($this->passportAuthenticationData($request->username, $request->password));
            $request = Request::create(env('PASSPORT_SERVER_URL') . '/oauth/token', 'POST');
            $response = Route::dispatch($request);
            $errorCode = $response->getStatusCode();
            $auth_server_response = json_decode((string) $response->content(), true);
            return response()->json($auth_server_response, $errorCode);
        } catch (Exception $e) {
            return response()->json('Authentication has failed!', 401);
        }
    }

    public function logout(Request $request)
    {
        $accessToken = $request->user()->token();
        $token = $request->user()->tokens->find($accessToken);
        $token->revoke();
        $token->delete();
        return response(['msg' => 'Token revoked'], 200);
    }
    public function register(Request $request)
    {
        return $this->registerCliente($request);
    }

    public function registerUser(Request $request)
    {
        $validatedData = $request->validate([
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);
        if (User::where('email', '=', $validatedData['email'])->exists()) {
            return response()->json(['error' => 'Email já existe'], 401);
        }
         $user = User::create([
             'password' => bcrypt($validatedData['password']),
             'name' => $validatedData['name'],
             'email' => $validatedData['email'],
             'remeber_token' => 'null',
             'email_verified_at' => 'null',
         ]);
         $user->save();

        $token = $user->createToken('auth_token')->accessToken;
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function registerCliente(Request $request)
    {
        $validatedData = $request->validate([
            'phone_number' => 'required|string|regex:/^9[1236]\d{7}$/|unique:vcards',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'photo_url' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'confirmation_code' => 'required|digits:4',
        ]);
        if (Vcard::where('phone_number', '=', $validatedData['phone_number'])->exists()) {
            return response()->json(['error' => 'Número de telefone já existe'], 401);
        }

        if (User::where('email', '=', $validatedData['email'])->exists()) {
            return response()->json(['error' => 'Email já existe'], 401);
        }

        $dataValidated = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^9[1236]\d{7}$/|unique:vcards',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'photo_url' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'confirmation_code' => 'required|digits:4',
        ]);
        if($dataValidated->fails()){
            return response()->json(['error' => $dataValidated->errors()->all()], 422);
        }

        try{

             if ($request->hasFile('photo_url')) {
                 $randomString = Str::random(6); 
                 $file = $request->file('photo_url');
                 $filename = $request->phone_number . '_' . $randomString . '.' . $file->getClientOriginalExtension();
                 $file->storeAs('fotos', $filename, 'public');
             }else{
                 $filename = 'default.png';
                
             }
            $vCard = Vcard::create([
                'phone_number' => $validatedData['phone_number'],
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'confirmation_code' => bcrypt($validatedData['confirmation_code']),
                'blocked' => 0,
                'balance' => 0,
                'max_debit' => 5000,
                'photo_url' => $filename,
            ]);

            try {
                $defaultCategories = DefaultCategory::all();
                foreach ($defaultCategories as $defaultCategory) {
                    Category::create([
                        'vcard' => $validatedData['phone_number'], 
                        'type' => $defaultCategory->type,
                        'name' => $defaultCategory->name,
                    ]);
                }
            }catch(Exception $e){
                return response()->json(['error' => 'Erro ao criar as categorias'], 401);
            }
            return response()->json([
                'vCard' => $vCard,
            ]);
        }
        catch(Exception $e){
            return response()->json(['error' => 'Erro ao criar o VCard'], 401);
        }        
    }
        
    public function verifyPassword(Request $request)
    {
        $enteredPassword = $request->input('entered_password');
        $userType = $request->input('user_type');
        if ($userType == 'user') {
            $user = User::where('id', '=', $request->user)->firstOrFail();
        } else {
            $user = Vcard::where('phone_number', '=', $request->user)->firstOrFail();
        }


        if (Hash::check($enteredPassword, $user->password)) {

            return response()->json(['message' => 'Password is correct'], 200);
        } else {

            return response()->json(['message' => 'Password is incorrecttttt'], 400);
        }
    }

 
}