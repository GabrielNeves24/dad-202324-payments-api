<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\VCard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Create a credit transaction for a vCard.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createCreditTransaction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vcard_phone_number' => 'required|string',
            'type' => 'required|string', // Assuming 'type' can be 'C' for credit
            'value' => 'required|numeric|min:0.01',
            'payment_type' => 'required|string',
            'payment_reference' => 'required|string',
            // Additional validation for other fields as needed
        ]);

        $vCard = VCard::where('phone_number', $data['vcard_phone_number'])->first();

        if (!$vCard) {
            return response()->json(['error' => 'VCard not found'], 404);
        }

        // Create a transaction record
        $transaction = Transaction::create([
            'vcard' => $vCard->phone_number,
            'type' => $data['type'],
            'value' => $data['value'],
            'payment_type' => $data['payment_type'],
            'payment_reference' => $data['payment_reference'],
            // Set other transaction data accordingly
        ]);

        return response()->json(['message' => 'Credit transaction created successfully', 'transaction' => $transaction], 201);
    }

    /**
     * Create a debit transaction for a vCard.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createDebitTransaction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vcard_phone_number' => 'required|string',
            'type' => 'required|string', // Assuming 'type' can be 'D' for debit
            'value' => 'required|numeric|min:0.01',
            'payment_type' => 'required|string',
            'payment_reference' => 'required|string',
            // Additional validation for other fields as needed
        ]);

        $vCard = VCard::where('phone_number', $data['vcard_phone_number'])->first();

        if (!$vCard) {
            return response()->json(['error' => 'VCard not found'], 404);
        }

        // Create a transaction record
        $transaction = Transaction::create([
            'vcard' => $vCard->phone_number,
            'type' => $data['type'],
            'value' => $data['value'],
            'payment_type' => $data['payment_type'],
            'payment_reference' => $data['payment_reference'],
            // Set other transaction data accordingly
        ]);

        return response()->json(['message' => 'Debit transaction created successfully', 'transaction' => $transaction], 201);
    }
}
