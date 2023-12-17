<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    public function createCreditTransaction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string',
            'reference' => 'required|string',
            'value' => 'required|numeric|min:0.01',
        ]);

        $response = $this->paymentGatewayService->createCredit($data);

        if ($response['success']) {
            return response()->json(['message' => 'Credit transaction created successfully'], 201);
        } else {
            return response()->json(['error' => $response['message']], 422);
        }
    }

    public function createDebitTransaction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string',
            'reference' => 'required|string',
            'value' => 'required|numeric|min:0.01',
        ]);
        $response = $this->paymentGatewayService->createDebit($data);

        if ($response['success']) {
            return response()->json(['message' => 'Debit transaction created successfully'], 201);
        } else {
            return response()->json(['error' => $response['message']], 422);
        }
    }
}
