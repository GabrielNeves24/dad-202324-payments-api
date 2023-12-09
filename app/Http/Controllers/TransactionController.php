<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\VCard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Create a credit transaction for a vCard.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request) : JsonResponse
    {
        $request->validate([
            'vcard' => 'required|max:9',
            'value' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:VCARD,MBWAY,MB,IBAN,VISA',
            'payment_reference' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:255',
            // Add any other validation rules for custom_options and custom_data
        ]);
        $vCardOrigem = VCard::where('phone_number', $request['vcard'])->first();
        if($vCardOrigem->blocked == 1){
            return response()->json(['error' => 'VCard blocked'], 403);
        }
        if (!$vCardOrigem) {
            return response()->json(['error' => 'VCard not found'], 404);
        }

        if($request['payment_type'] != 'VCARD'){
            $old_balance = $vCardOrigem->balance;
            $new_balance = $old_balance - $request['value'];

            // Create a transaction record
            $transactionOrigem = new Transaction([
                'vcard' => $vCardOrigem->phone_number,
                'type' => 'C',
                'value' => $request['value'],
                'old_balance' => $old_balance,
                'new_balance' => $new_balance,
                'payment_type' => $request['payment_type'],
                'payment_reference' => $request['payment_reference'],
                'category_id' => $request['category_id'],
                'description' => $request['description'],
                'date' => now()->toDateString(),
                'datetime' => now(),
                // Set other transaction data accordingly
            ]);
            $transactionOrigem->save();
            $vCardOrigem->balance -= $request['value'];
            $vCardOrigem->save();
            return response()->json([
                'message' => 'Transação criada com sucesso (VCARD - VCARD)',
            ], 201);
        }else{
            $vCardDestino = VCard::where('phone_number', $request['payment_reference'])->first();
            if (!$vCardDestino) {
                return response()->json(['error' => 'VCard Destino não existe'], 404);
            }
            if ($vCardDestino->blocked == 1) {
                return response()->json(['error' => 'VCard Destino Bloqueado'], 404);
            }
            try {
                $old_balance = $vCardOrigem->balance;
                $new_balance = $old_balance - $request['value'];

                // Create a transaction record
                $transactionOrigem = new Transaction([
                    'vcard' => $vCardOrigem->phone_number,
                    'type' => 'D',
                    'value' => $request['value'],
                    'old_balance' => $old_balance,
                    'new_balance' => $new_balance,
                    'payment_type' => $request['payment_type'],
                    'payment_reference' => $request['payment_reference'],
                    'category_id' => $request['category_id'],
                    'pair_vcard' => $vCardDestino->phone_number, // Pair with the destination vCard
                    'description' => $request['description'],
                    'date' => now()->toDateString(),
                    'datetime' => now(),
                    // Set other transaction data accordingly
                ]);
                $transactionOrigem->save(); // Save the debit transaction

                // Retrieve the ID of the debit transaction
                $debitTransactionId = $transactionOrigem->id;

                // Create a credit transaction record with the same debit transaction ID as pair_transaction
                $transactionDestino = new Transaction([
                    'vcard' => $vCardDestino->phone_number,
                    'type' => 'C',
                    'value' => $request['value'],
                    'old_balance' => $vCardDestino->balance,
                    'new_balance' => $vCardDestino->balance + $request['value'],
                    'payment_type' => $request['payment_type'],
                    'payment_reference' => $request['vcard'],
                    'description' => $request['description'],
                    'date' => now()->toDateString(),
                    'datetime' => now(),
                    'pair_transaction' => $debitTransactionId, // Pair with the debit transaction
                    // Set other transaction data accordingly
                ]);

                $transactionDestino->save(); // Save the credit transaction
                // Update the existing transactions with paired transaction IDs
                $existingDebitTransaction = Transaction::where('id', $debitTransactionId)->first();
                $existingDebitTransaction->pair_transaction = $debitTransactionId+1;
                $existingDebitTransaction->save();

                // Update the balance of vCardOrigem and vCardDestino
                $vCardOrigem->balance -= $request['value'];
                $vCardOrigem->save();

                $vCardDestino->balance += $request['value'];
                $vCardDestino->save();

                return response()->json([
                    'message' => 'Transação criada com sucesso',
                ], 201);
            }catch (\Exception $e) {
                // Handle any exceptions and return an error response
                return response()->json(['error' => 'An error occurred while creating the transaction', 'details' => $e->getMessage()], 500);
            }
        }
        return response()->json(['error' => 'An unexpected error occurred'], 500);
    }

    public function storeCredit(Request $request) : JsonResponse
    {
            $request->validate([
                'vcard' => 'required|max:9',
                'value' => 'required|numeric|min:0.01',
                'payment_type' => 'required',
                'payment_reference' => 'required',
                'category_id' => 'nullable',
                'description' => 'nullable|string|max:255',
                // Add any other validation rules for custom_options and custom_data
            ]);

            $vCardOrigem = VCard::where('phone_number', $request['vcard'])->first();
            //if vcard is blocked return error
            if($vCardOrigem->blocked == 1){
                return response()->json(['error' => 'VCard blocked'], 403);
            }
            if (!$vCardOrigem) {
                return response()->json(['error' => 'VCard not found'], 404);
            }
            try {
                $old_balance = $vCardOrigem->balance;
                $new_balance = $old_balance + $request['value'];

                // Create a transaction record
                $transactionOrigem = new Transaction([
                    'vcard' => $vCardOrigem->phone_number,
                    'type' => 'C',
                    'value' => $request['value'],
                    'old_balance' => $old_balance,
                    'new_balance' => $new_balance,
                    'payment_type' => $request['payment_type'],
                    'payment_reference' => $request['payment_reference'],
                    'description' => $request['description'],
                    'date' => now()->toDateString(),
                    'datetime' => now(),
                    // Set other transaction data accordingly
                ]);
                $transactionOrigem->save(); // Save the debit transaction
                //update value of balance vcard
                $vCardOrigem->balance += $request['value'];
                $vCardOrigem->save();
                return response()->json([
                    'message' => 'Transação Credito criada com sucesso',
                    'data' => $transactionOrigem
                ], 201);
            }catch (\Exception $e) {
                // Handle any exceptions and return an error response
                return response()->json(['error' => 'An error occurred while creating the transaction', 'details' => $e->getMessage()], 500);
            }
        //}
        return response()->json(['error' => 'An unexpected error occurred'], 500);
    }


    public function getTotalTransactionByPaymentTypeDebit(Request $request)
    {
        //return data for a char as transationData
        $ransactionByPaymeneType = Transaction::select('payment_type', DB::raw('count(*) as total'))
            ->where('type', 'D')
            ->groupBy('payment_type')
            ->get();
            //dd($transactionByPaymeneType);

        return response()->json(['data' => $ransactionByPaymeneType], 200);
        
    } 
    
    public function getTotalTransactionByPaymentTypeCredit(Request $request)
    {
        //return data for a char as transationData
        $ransactionByPaymeneType = Transaction::select('payment_type', DB::raw('count(*) as total'))
            ->where('type', 'C')
            ->groupBy('payment_type')
            ->get();

        return response()->json(['data' => $ransactionByPaymeneType], 200);
        
    }

    public function getAllTrasacionsByNumber($phone_number)
    {
        $transaction = Transaction::all()->where('vcard', $phone_number);
        return $transaction;
        //return response()->json(['transaction' => $transaction], 200);
        //return response()->json(['vcard' => $vcard], 200);
    }

    public function getAllTransactions()
    {
        $transaction = Transaction::all();
        //return $transaction;
        return response()->json(['data' => $transaction], 200);
        //return response()->json(['vcard' => $vcard], 200);
    }

    public function GetTransactionById($id)
    {
        $transaction = Transaction::find($id);
        return response()->json(['data' => $transaction], 200);
    }

    public function updateTransactionById(Request $request)
    {
        //it can only update the description and category_id on Category Table
        $transaction = Transaction::find($request->id);
        //validate data
        $request->validate([
            'description' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        //update data if altered
        if ($request->description != null) {
            $transaction->description = $request->description;
        }
        if ($request->category_id != null) {
            $transaction->category_id = $request->category_id;
        }
        $transaction->save();
        return response()->json(['data' => $transaction], 200);
    }
}
