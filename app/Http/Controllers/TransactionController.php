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
        // if ($request['payment_type'] == 'MB' || $request['payment_type'] == 'MBWAY' || $request['payment_type'] == 'PAYPAL' || $request['payment_type'] == 'IBAN' || $request['payment_type'] == 'VISA') {
        //     // Create a credit transaction
        //     $request->validate([
        //         'vcard' => 'required|max:9',
        //         'type' => 'required|in:C,D',
        //         'value' => 'required|numeric|min:0.01',
        //         'payment_type' => 'required|in:VCARD,MBWAY,PAYPAL,IBAN,MB,VISA',
        //         'payment_reference' => 'required|string|max:255',
        //         'pair_transaction' => 'nullable|exists:transactions,id',
        //         'pair_vcard' => 'nullable|string|max:9',
        //         'category_id' => 'nullable|exists:categories,id',
        //         'description' => 'nullable|string|max:255',
        //         // Add any other validation rules for custom_options and custom_data
        //     ]);
    
        //     $vCard = VCard::where('phone_number', $request['vcard'])->first();
    
        //     if (!$vCard) {
        //         return response()->json(['error' => 'VCard not found'], 404);
        //     }
        //     $old_balance = $vCard->balance;
        //     $new_balance = $old_balance - $request['value'];
    
        //     // Create a transaction record
        //     $transaction = Transaction::create([
        //         'vcard' => $vCard->phone_number,
        //         'type' => $request['type'],
        //         'value' => $request['value'],
        //         'old_balance' => $old_balance,
        //         'new_balance' => $new_balance,
        //         'payment_type' => $request['payment_type'],
        //         'payment_reference' => $request['payment_reference'],
        //         'category_id' => $request['category_id'], 
        //         'description' => $request['description'],
        //         'date' => now()->toDateString(),
        //         'datetime' => now(),
        //         // Set other transaction data accordingly
        //     ]);
        //     $transaction->save();

        //     // Remove the amount from the balance on vCard
        //     $vCard->balance = $vCard->balance - $request['value'];
        //     $vCard->save();
    
        //     return response()->json(['message' => 'Transação criada com sucesso', 'transaction' => $transaction], 201);
        // } 
        if ($request['payment_type'] == 'VCARD' ) {
            //now in this case is the same as the debit transaction but i will recevied one more filed the A vCard payment type means that the payment is relative
            //to a transfer between 2 vCards and 2 transactions (paired transactions) are created – a debit
            //transaction on the source vCard (the vCard where the payment is created) and a credit transaction
            //on the destination vCard. vCard payments are handled exclusively by the vCard platform
            $request->validate([
                'vcard' => 'required|max:9',
                'type' => 'required|in:C,D',
                'value' => 'required|numeric|min:0.01',
                'payment_type' => 'required|in:VCARD',
                'payment_reference' => 'required|max:9',
                'category_id' => 'nullable|exists:categories,id',
                'description' => 'nullable|string|max:255',
                // Add any other validation rules for custom_options and custom_data
            ]);

            $vCardOrigem = VCard::where('phone_number', $request['vcard'])->first();
            $vCardDestino = VCard::where('phone_number', $request['payment_reference'])->first();
            if (!$vCardOrigem) {
                return response()->json(['error' => 'VCard not found'], 404);
            }
            if (!$vCardDestino) {
                return response()->json(['error' => 'VCard Destino não existe'], 404);
            }
            try {
                $old_balance = $vCardOrigem->balance;
                $new_balance = $old_balance - $request['value'];

                // Create a transaction record
                $transactionOrigem = new Transaction([
                    'vcard' => $vCardOrigem->phone_number,
                    'type' => $request['type'],
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
      
        // if ($request['payment_type'] == 'VCARD' ) {
            //now in this case is the same as the debit transaction but i will recevied one more filed the A vCard payment type means that the payment is relative
            //to a transfer between 2 vCards and 2 transactions (paired transactions) are created – a debit
            //transaction on the source vCard (the vCard where the payment is created) and a credit transaction
            //on the destination vCard. vCard payments are handled exclusively by the vCard platform
            $request->validate([
                'vcard' => 'required|max:9',
                'value' => 'required|numeric|min:0.01',
                'payment_type' => 'required',
                'payment_reference' => 'required|max:9',
                'category_id' => 'nullable',
                'description' => 'nullable|string|max:255',
                // Add any other validation rules for custom_options and custom_data
            ]);

            $vCardOrigem = VCard::where('phone_number', $request['vcard'])->first();
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





    // /**
    //  * Create a debit transaction for a vCard.
    //  *
    //  * @param  Request  $request
    //  * @return JsonResponse
    //  */
    // public function createDebitTransaction(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'vcard' => 'required|string|max:9',
    //         'type' => 'required|in:C,D',
    //         'value' => 'required|numeric|min:0.01',
    //         'old_balance' => 'required|numeric',
    //         'new_balance' => 'required|numeric',
    //         'payment_type' => 'required|in:VCARD,MBWAY,PAYPAL,IBAN,MB,VISA',
    //         'payment_reference' => 'required|string|max:255',
    //         'pair_transaction' => 'nullable|exists:transactions,id',
    //         'pair_vcard' => 'nullable|string|max:9',
    //         'category_id' => 'nullable|exists:categories,id',
    //         'description' => 'nullable|string|max:255',
    //         // Add any other validation rules for custom_options and custom_data
    //     ]);

    //     $vCard = VCard::where('phone_number', $request['vcard'])->first();

    //     if (!$vCard) {
    //         return response()->json(['error' => 'VCard not found'], 404);
    //     }

    //     // Create a transaction record
    //     $transaction = Transaction::create([
    //         'vcard' => $vCard->phone_number,
    //         'type' => $request['type'],
    //         'value' => $request['value'],
    //         'old_balance' => $request['old_balance'],
    //         'new_balance' => $request['new_balance'],
    //         'payment_type' => $request['payment_type'],
    //         'payment_reference' => $request['payment_reference'],
    //         'description' => $request['description'],
    //         'date' => $request['date'],
    //         'datetime' => $request['datetime'],
    //         // Set other transaction data accordingly
    //     ]);

    //     // Remove the amount from the balance on vCard
    //     $vCard->balance = $vCard->balance - $request['value'];
    //     $vCard->save();

    //     return response()->json(['message' => 'Transação criada com sucesso', 'transaction' => $transaction], 201);
    // }


    // public function createCreditTransaction(Request $request): JsonResponse
    // {
    //     $data = $request->validate([
    //         'vcard_phone_number' => 'required|string',
    //         'type' => 'required|string', // Assuming 'type' can be 'C' for credit
    //         'value' => 'required|numeric|min:0.01',
    //         'payment_type' => 'required|string',
    //         'payment_reference' => 'required|string',
    //         // Additional validation for other fields as needed
    //     ]);

    //     $vCard = VCard::where('phone_number', $data['vcard_phone_number'])->first();

    //     if (!$vCard) {
    //         return response()->json(['error' => 'VCard not found'], 404);
    //     }

    //     // Create a transaction record
    //     $transaction = Transaction::create([
    //         'vcard' => $vCard->phone_number,
    //         'type' => $data['type'],
    //         'value' => $data['value'],
    //         'payment_type' => $data['payment_type'],
    //         'payment_reference' => $data['payment_reference'],
    //         // Set other transaction data accordingly
    //     ]);

    //     return response()->json(['message' => 'Credit transaction created successfully', 'transaction' => $transaction], 201);
    // }

    

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
