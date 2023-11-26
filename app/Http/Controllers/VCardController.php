<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\VCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\DefaultCategory;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class VCardController extends Controller
{
    public function index(){
        $vcards = VCard::all();
        return response()->json(['vcards' => $vcards], 200);
    }

    public function getAllVCards()
    {
        $vcards = VCard::all();
        return response()->json(['data' => $vcards], 200);
    }

    public function getVCardsbyphoneNumber($phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        //return $vcard;
        return response()->json(['data' => $vcard], 200);
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

    public function update($phone_number, Request $request)
    {
        if($request->filled('name') && $request->filled('email')){
            $vcard = VCard::findOrFail($phone_number);
            // Validate the request
            $this->validate($request, [
                'name' => 'string',
                'email' => 'email|unique:users,email,' . $vcard->id,
            ]);

            // Update the user
            $vcard->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            return response()->json(['vcard' => $vcard], 200);
        }else 
        if($request->filled('password')){
            // Validate the request
            $validated = Validator::make($request->all(), [
                'password' => 'required|string|min:6',
                'confirmation_code' => 'required|integer|digits:4',
            ])->validate();

            // Find the VCard
            $vcard = VCard::find($phone_number);
            if (!$vcard) {
                return response()->json(['error' => 'VCard not found'], 404);
            }

            // Update the VCard
            $vcard->password = Hash::make($validated['password']);
            $vcard->confirmation_code = Hash::make($validated['confirmation_code']);
            $vcard->save();

            return response()->json(['message' => 'VCard password updated successfully'], 200);
        }else{
            return response()->json(['message' => 'VCard not updated'], 404);
        }
    }

    // public function updateProfile(Request $request, $phone_number)
    // {
    //     $vcard = VCard::findOrFail($phone_number);

    //     // Validate the request
    //     $this->validate($request, [
    //         'name' => 'string',
    //         'email' => 'email|unique:users,email,' . $vcard->id,
    //         'password' => 'required',
    //     ]);

    //     // Check if the password is correct
    //     if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->password])) {
    //         return response()->json(['error' => 'Invalid password'], 401);
    //     }

    //     // Update the user
    //     $vcard->update([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //     ]);

    //     return response()->json(['vcard' => $vcard], 200);
    // }

    

    public function getLast30DaysTransactions($phone_number)
{
    $startDate = Carbon::now()->subDays(30);
    $endDate = Carbon::now();

    $transactions = Transaction::where('vcard', $phone_number)
                                ->whereBetween('date', [$startDate, $endDate])
                                ->orderBy('date', 'asc')
                                ->get();

    if($transactions->isEmpty()){
        return response()->json(['message' => "No transactions found for the last 30 days for vCard $phone_number"], 404);
    }

    return response()->json(['data' => $transactions], 200);
}

    public function getCategoriesbyphoneNumberDebit($phone_number)
    {
        //$vcard = VCard::findOrFail($phone_number);
        $categories = Category::where('vcard', $phone_number)->where('type', 'D')->get();
        //caso vazia sem categorias
        if($categories->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem categorias`], 404);
        }
        return response()->json(['data' => $categories], 200);
    }

    public function getCategoriesbyphoneNumberCredit($phone_number)
    {
        //$vcard = VCard::findOrFail($phone_number);
        $categories = Category::where('vcard', $phone_number)->where('type', 'C')->get();
        //caso vazia sem categorias
        if($categories->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem categorias`], 404);
        }
        return response()->json(['data' => $categories], 200);
    }

    public function getTransactionsbyphoneNumber($phone_number)
    {
        //$vcard = VCard::findOrFail($phone_number);
        $transactions = Transaction::where('vcard', $phone_number)->orderBy('date','desc')->get();
        //caso vazia sem categorias
        if($transactions->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem transações`], 404);
        }
        return response()->json(['data' => $transactions], 200);
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

    // public function updatePassword(Request $request, $phone_number)
    // {
    //     $vcard = VCard::findOrFail($phone_number);

    //     // Validate the request
    //     $this->validate($request, [
    //         'current_password' => 'required',
    //         'new_password' => 'required|confirmed',
    //     ]);

    //     // Check if the current password is correct
    //     if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->current_password])) {
    //         return response()->json(['error' => 'Invalid password'], 401);
    //     }

    //     // Update the password
    //     $vcard->update([
    //         'password' => bcrypt($request->new_password),
    //     ]);

    //     return response()->json(['vcard' => $vcard], 200);
    // }

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
            if (!Transaction::where('vcard', $phone_number)->exists()){
                VCard::where('phone_number', $phone_number)->forceDelete();
                return response()->json(['message' => `VCard $nome eliminado permanentemente com sucesso`], 200);
            }
        }    
        $vcard = VCard::findOrFail($phone_number);
        $vcard->delete();

        return response()->json(['message' => `VCard $nome eliminado (Soft) com sucesso`], 200);
    }

    public function updateVCard(Request $request, $id){
        dd($request);
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'id' => 'integer',
            'name' => 'string|max:255',
            'email' => 'email|unique:vcards,email,' . $id,
            'photo_url' => 'nullable|image',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $dataValidated = $validator->validated();
        
        // Check if the VCard with the provided phone number exists
        $vcard = VCard::where('phone_number', $id)->first();

        if (!$vcard) {
            return response()->json(['message' => "VCard $id não encontrado"], 404);

        }
        // Update only if fields are present and not empty
        if ($request->filled('name')) {
            $vcard->name = $dataValidated['name'];

        }
        if ($request->filled('email')) {
            $vcard->email = $dataValidated['email'];
        }


        if ($request->hasFile('photo_url')) {
            $randomString = Str::random(6); // Using Laravel's Str::random for generating random string
            $file = $request->file('photo_url');
            $filename = $vcard->phone_number . '_' . $randomString . '.' . $file->getClientOriginalExtension();
            $file->storeAs('fotos', $filename, 'public');
            $vcard->photo_url = $filename;
            //$vCard->save();
        }

        $vcard->save();

        return response()->json(['message' => "VCard $id atualizado com sucesso"], 200);
    }


    public function updateVCardUser(Request $request, $phone_number){
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
        return response()->json(['data' => `VCard $teste atualizado com sucesso`], 200);
    }


    public function deleteCategorybyphoneNumber(Request $request, $id){
        // Validate the incoming request data
        // $validatedData = $request->validate([
        //     'id' => 'required|integer', // Add any validation rules you need
        // ]);
        $id = $request->id;
        $category = Category::findOrFail($id);
        //if category has alreay bem used in transactions, do soft delete only else delete
        if(Transaction::where('category_id', $id)->exists()){
            //delete only at delete_at i dont have updated_at
            $category->delete();
            return response()->json(['message' => `Categoria $id eliminada (Soft) com sucesso`], 200);
        }else{
            $category->forceDelete();
            return response()->json(['message' => `Categoria $id eliminada permanentemente com sucesso`], 200);
        }
    }

    public function getCategorybyphoneNumberAndId($phone_number, $id)
    {
        //$vcard = VCard::findOrFail($phone_number);
        $category = Category::findOrFail($id);
        // caso nao exista devolve erro
        if(!$category){
            return response()->json(['message' => `Categoria $id não encontrada`], 404);
        }
        return response()->json(['data' => $category], 200);
    }

    

    public function updatePasswordVCard(Request $request, $id){
        // Validate the incoming request data
        $validatedData = $request->validate([
            'id' => 'required|integer', 
            'confirmation_code' => 'integer|digits:4',
            'password' => 'required|string',
        ]);

        // Check if the VCard with the provided phone number exists
        $vcard = VCard::where('phone_number', $request->id)->first();

        if (!$vcard) {
            return response()->json(['message' => "VCard $request->id não encontrado"], 404);
        }

        if ($request->filled('password')) {
            $vcard->password = Hash::make($validatedData['password']);
        }
        if ($request->filled('confirmation_code')) {
            $vcard->confirmation_code = $validatedData['confirmation_code'];
        }
        $vcard->save();

        return response()->json(['message' => "VCard $request->id atualizado com sucesso"], 200);
    }
  
}
