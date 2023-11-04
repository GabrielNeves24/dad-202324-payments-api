<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\VCard;
use Illuminate\Support\Facades\Auth;

class VCardController extends Controller
{
    public function index(){
        $vcards = VCard::all();
        return response()->json(['vcards' => $vcards], 200);
    }

    public function getAllVCards()
    {
        $vcards = VCard::all();
        return response()->json(['vcards' => $vcards], 200);
    }

    public function getVCardsbyphoneNumber($phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        return $vcard;
        //return response()->json(['vcard' => $vcard], 200);
    }

    public function getVCardImage($phone_number)
    {
        // Find the user's image file path
        $vcard = VCard::findOrFail($phone_number);
        $imagePath = public_path("storage/fotos/{$vcard->photo_url}");

        // Check if the image file exists
        if (file_exists($imagePath)) {
            // Return the image as a response
            return response()->file($imagePath, ['Content-Type' => 'image/jpg']);
        }

        // If the image doesn't exist, return a default image or an error response
        $defaultImagePath = public_path('storage/fotos/default.jpg');
        if (file_exists($defaultImagePath)) {
            return response()->file($defaultImagePath, ['Content-Type' => 'image/jpg']);
        } else {
            // If the default image doesn't exist, you can return a 404 response
            return response('Image not found', 404);
        }
    }

    public function updateProfile(Request $request, $phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);

        // Validate the request
        $this->validate($request, [
            'name' => 'string',
            'email' => 'email|unique:users,email,' . $vcard->id,
            'password' => 'required',
        ]);

        // Check if the password is correct
        if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Update the user
        $vcard->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json(['vcard' => $vcard], 200);
    }

    public function updateConfirmationCode(Request $request, $phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);

        // Validate the request
        $this->validate($request, [
            'confirmation_code' => 'required',
            'password' => 'required',
        ]);

        // Check if the password is correct
        if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Update the confirmation code
        $vcard->update([
            'confirmation_code' => $request->confirmation_code,
        ]);

        return response()->json(['vcard' => $vcard], 200);
    }

    public function updatePassword(Request $request, $phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);

        // Validate the request
        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        // Check if the current password is correct
        if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->current_password])) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Update the password
        $vcard->update([
            'password' => bcrypt($request->new_password),
        ]);

        return response()->json(['vcard' => $vcard], 200);
    }

    public function updatePhoto(Request $request, $phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);

        // Validate the request
        $this->validate($request, [
            'photo' => 'required|image',
            'password' => 'required',
        ]);

        // Check if the password is correct
        if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Save the new photo
        $photo = $request->file('photo');
        $photoName = time() . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs('public/fotos', $photoName);

        // Update the user's photo URL
        $vcard->update([
            'photo_url' => $photoName,
        ]);

        return response()->json(['vcard' => $vcard], 200);
    }

    public function deleteVCard($phone_number)
    {
        $nome = VCard::where('phone_number', $phone_number)->value('name');
        //caso o vcard não exista, retorna erro
        if(!VCard::where('phone_number', $phone_number)->exists()){
            return response()->json(['message' => `VCard $phone_number não encontrado`], 404);
        }
        //caso exista, deleta, caso esteja com saldo a 0 e sem dados de transações associados force delte
        if(VCard::where('phone_number', $phone_number)->value('balance') == 0){
            if (!Transaction::where('phone_number', $phone_number)->exists()){
                VCard::where('phone_number', $phone_number)->forceDelete();
                return response()->json(['message' => `VCard $nome eliminado permanentemente com sucesso`], 200);
            }
        }    
        $vcard = VCard::findOrFail($phone_number);
        $vcard->delete();

        return response()->json(['message' => `VCard $nome eliminado (Soft) com sucesso`], 200);
    }

    public function updateVCard(Request $request, $phone_number){
        //caso o vcard não exista, retorna erro
        $teste = $phone_number;
        if(!VCard::where('phone_number', $phone_number)->exists()){
            return response()->json(['message' => `VCard $phone_number não encontrado`], 404);
        }
        $vcard = VCard::findOrFail($phone_number);
        //update only max_debit
        $vcard->update([
            'max_debit' => $request->max_debit,
            'blocked' => $request->blocked,
        ]);
        return response()->json(['message' => `VCard $teste atualizado com sucesso`], 200);
    }
}
