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
use Illuminate\Support\Facades\DB;

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
        return response()->json(['data' => $vcard], 200);
    }

    public function getVCardImage($phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        $imagePath = public_path("storage/fotos/{$vcard->photo_url}");
        if (file_exists($imagePath)) {
            return response()->file($imagePath, ['Content-Type' => 'image/jpg']);
        }
        $defaultImagePath = public_path('storage/fotos/default.jpg');
        if (file_exists($defaultImagePath)) {
            return response()->file($defaultImagePath, ['Content-Type' => 'image/jpg']);
        } else {
            return response('Image not found', 404);
        }
    }

    

    public function update($phone_number, Request $request)
    {
        
        if($request->filled('name') && $request->filled('email')){
            $vcard = VCard::findOrFail($phone_number);
            $this->validate($request, [
                'name' => 'string',
                'email' => 'email|unique:users,email,' . $vcard->id,
                'photo_url' => 'nullable|max:4096',
            ]);
            if ($request->hasFile('photo_url')) {
                $randomString = Str::random(6); 
                $file = $request->file('photo_url');
                $filename = $vcard->phone_number . '_' . $randomString . '.' . $file->getClientOriginalExtension();
                $file->storeAs('fotos', $filename, 'public');
                $vcard->photo_url = $filename;
                $vcard->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'photo_url' => $filename,
                ]); 
                return response()->json(['vcard' => $vcard], 200);
            }else{
                $vcard->update([
                    'name' => $request->name,
                    'email' => $request->email,
                ]);
                return response()->json(['vcard' => $vcard], 200);
            }
            
        }else 
        if($request->filled('password') || $request->filled('confirmation_code')){
            $vcard = VCard::find($phone_number);
            if (!$vcard) {
                return response()->json(['error' => 'VCard not found'], 404);
            }
            if ($request->filled('password')) {
                $validated =$this->validate($request, [
                    'password' => 'string|min:6',
                ]);
                $vcard->password = Hash::make($validated['password']);
            }
            if ($request->filled('confirmation_code')) {
                $validated =$this->validate($request, [
                    'confirmation_code' => 'integer|digits:4',
                ]);
                $vcard->confirmation_code = Hash::make($validated['confirmation_code']);
            }
            $vcard->save();
            return response()->json(['message' => 'VCard password updated successfully'], 200);
        }else{
            return response()->json(['message' => 'VCard not updated'], 404);
        }
    }

    public function getTransactionsByMonth(Request $request)
    {
        $transactions = Transaction::select(
            DB::raw('MONTH(datetime) as month'),
            DB::raw('SUM(CASE WHEN type = "C" THEN value ELSE -value END) as total_value'),
            DB::raw('AVG(value) as avg_value'),
            DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('month')
            ->get();
        $result = $transactions->map(function ($item) {
            return [
                'name' => date("M", mktime(0, 0, 0, $item->month, 1, 2000)), 
                'pl' => $item->total_value,
                'avg' => $item->avg_value,
                'inc' => $item->transaction_count, 
            ];
        });

        return response()->json(['date' => $result ], 201);
    }

    public function getTransactionsByDay(Request $request){
        $transactions = Transaction::select(
            DB::raw('DAY(datetime) as day'),
            DB::raw('SUM(CASE WHEN type = "C" THEN value ELSE -value END) as total_value'),
            DB::raw('AVG(value) as avg_value'),
            DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('day')
            ->get();
        $result = $transactions->map(function ($item) {
            return [
                'name' => date("d", mktime(0, 0, 0, 1, $item->day, 2000)), 
                'pl' => $item->total_value,
                'avg' => $item->avg_value,
                'inc' => $item->transaction_count, 
            ];
        });

        return response()->json(['date' => $result ], 201);
    }

    public function getCategorySpendingByVCard(Request $request,$phone_number)
    {
        $categorySpending = Transaction::select(
            'categories.name as category',
            DB::raw('SUM(transactions.value) as totalSpent')
        )
        ->join('categories', 'transactions.category_id', '=', 'categories.id')
        ->where('transactions.vcard', $phone_number)
        ->where('transactions.type', 'D') 
        ->groupBy('categories.name')
        ->get();

        $topCategories = $categorySpending->sortByDesc('totalSpent')->take(5);

        return response()->json([
            'categoryData' => $categorySpending,
            'topCategories' => $topCategories
        ]);
    }   

    public function getLast30DaysTransactions(Request $request, $phone_number)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $initialTransaction = Transaction::where('vcard', $phone_number)
            ->where('date', '>=', $thirtyDaysAgo)
            ->orderBy('date', 'asc')
            ->first(['old_balance']);
    
        $old_balance = $initialTransaction ? $initialTransaction->old_balance : 0;
        $transactions = Transaction::where('vcard', $phone_number)
            ->where('date', '>=', $thirtyDaysAgo)
            ->groupBy(DB::raw('DATE(date)')) 
            ->orderBy('date', 'asc')
            ->get([
                DB::raw('DATE(date) as date'),
                DB::raw('SUM(CASE WHEN type = "C" THEN value ELSE 0 END) as daily_credit'),
                DB::raw('SUM(CASE WHEN type = "D" THEN value ELSE 0 END) as daily_debit')
            ]);

        $running_balance = $old_balance;
        $formattedTransactions = $transactions->map(function ($transaction) use (&$running_balance) {
            $date = Carbon::createFromFormat('Y-m-d', $transaction->date);
            
            $running_balance += $transaction->daily_credit;
            $running_balance -= $transaction->daily_debit;
    
            return [
                'date' => $date->format('d/m'), 
                'daily_credit' => $transaction->daily_credit,
                'daily_debit' => $transaction->daily_debit,
                'running_balance' => $running_balance
            ];
        });
    
        return response()->json(['data' => $formattedTransactions]);
    }
    

    public function getCategoriesbyphoneNumberDebit($phone_number)
    {
        $categories = Category::where('vcard', $phone_number)->where('type', 'D')->get();
        if($categories->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem categorias`], 404);
        }
        return response()->json(['data' => $categories], 200);
    }

    public function getCategoriesbyphoneNumberCredit($phone_number)
    {
        $categories = Category::where('vcard', $phone_number)->where('type', 'C')->get();
        if($categories->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem categorias`], 404);
        }
        return response()->json(['data' => $categories], 200);
    }

    public function getTransactionsbyphoneNumber($phone_number)
    {
        $transactions = Transaction::where('vcard', $phone_number)->orderBy('date','desc')->get();
        if($transactions->isEmpty()){
            return response()->json(['message' => `VCard $phone_number não tem transações`], 404);
        }
        return response()->json(['data' => $transactions], 200);
    }

    public function updateConfirmationCode(Request $request, $phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        $this->validate($request, [
            'confirmation_code' => 'integer|digits:4',
            'password' => 'string|min:6',
        ]);

        if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Invalid password'], 401);
        }
        $vcard->update([
            'confirmation_code' => $request->confirmation_code,
        ]);

        return response()->json(['vcard' => $vcard], 200);
    }
    public function updatePhoto(Request $request, $phone_number)
    {
        $vcard = VCard::findOrFail($phone_number);
        $this->validate($request, [
            'photo' => 'required|image',
            'password' => 'required',
        ]);

        if (!Auth::attempt(['email' => $vcard->email, 'password' => $request->password])) {
            return response()->json(['error' => 'Invalid password'], 401);
        }
        $photo = $request->file('photo');
        $photoName = time() . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs('public/fotos', $photoName);

        $vcard->update([
            'photo_url' => $photoName,
        ]);

        return response()->json(['vcard' => $vcard], 200);
    }

    public function deleteVCard($phone_number)
    {
        $vcard = VCard::where('phone_number', $phone_number);

        if (!$vcard->exists()) {
            return response()->json(['message' => "VCard $phone_number não encontrado"], 404);
        }

        $nome = $vcard->value('name');
        $balance = $vcard->value('balance');

        try {
            DB::beginTransaction();
            $hasTransactions = Transaction::where('vcard', $phone_number)->exists();
            if ($hasTransactions) {
                $categories = Category::where('vcard', $phone_number)->get();
                foreach ($categories as $category) {
                    $category->delete();
                }
            } else {
                $categories = Category::where('vcard', $phone_number)->get();
                foreach ($categories as $category) {
                    $category->forceDelete();
                }
            } 

            if ($balance == 0) {
                if ($hasTransactions) {
                    $vcard->delete();
                    DB::commit();
                    return response()->json(['message' => "VCard $nome eliminado (Soft Delete) com sucesso"], 200);
                } else {
                    $vcard->forceDelete();
                    DB::commit();
                    return response()->json(['message' => "VCard $nome eliminado permanentemente com sucesso"], 200);
                }
            } else {
                DB::rollBack();
                return response()->json(['message' => "VCard $nome não pode ser eliminado, saldo diferente de 0"], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => "Erro ao eliminar VCard: " . $e->getMessage()], 500);
        }
    }

    

    public function updateVCard(Request $request, $id){
        dd($request);
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
        $vcard = VCard::where('phone_number', $id)->first();

        if (!$vcard) {
            return response()->json(['message' => "VCard $id não encontrado"], 404);

        }
        if ($request->filled('name')) {
            $vcard->name = $dataValidated['name'];

        }
        if ($request->filled('email')) {
            $vcard->email = $dataValidated['email'];
        }


        if ($request->hasFile('photo_url')) {
            $randomString = Str::random(6); 
            $file = $request->file('photo_url');
            $filename = $vcard->phone_number . '_' . $randomString . '.' . $file->getClientOriginalExtension();
            $file->storeAs('fotos', $filename, 'public');
            $vcard->photo_url = $filename;
        }

        $vcard->save();

        return response()->json(['message' => "VCard $id atualizado com sucesso"], 200);
    }


    public function updateVCardUser(Request $request, $phone_number){

        $teste = $phone_number;
        if(!VCard::where('phone_number', $phone_number)->exists()){
            return response()->json(['message' => `VCard $phone_number não encontrado`], 404);
        }
        $vcard = VCard::findOrFail($phone_number);

        $vcard->update([
            'max_debit' => $request->max_debit,
            'blocked' => $request->blocked,
        ]);
        return response()->json(['data' => `VCard $teste atualizado com sucesso`], 200);
    }


    public function deleteCategorybyphoneNumber(Request $request, $id){

        $id = $request->id;
        $category = Category::findOrFail($id);
        if(Transaction::where('category_id', $id)->exists()){
            $category->delete();
            return response()->json(['message' => `Categoria $id eliminada (Soft) com sucesso`], 200);
        }else{
            $category->forceDelete();
            return response()->json(['message' => `Categoria $id eliminada permanentemente com sucesso`], 200);
        }
    }

    public function getCategorybyphoneNumberAndId($phone_number, $id)
    {

        $category = Category::findOrFail($id);

        if(!$category){
            return response()->json(['message' => `Categoria $id não encontrada`], 404);
        }
        return response()->json(['data' => $category], 200);
    }

    

    public function updatePasswordVCard(Request $request, $id){

        $validatedData = $request->validate([
            'id' => 'required|integer', 
            'confirmation_code' => 'integer|digits:4',
            'password' => 'required|string',
        ]);
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
