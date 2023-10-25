<?php

namespace App\Services;

use GuzzleHttp\Client;

class PaymentGatewayService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://dad-202324-payments-api.vercel.app',
        ]);
    }

    public function createCredit($data)
    {
        return $this->sendRequest('POST', '/api/credit', $data);
    }

    public function createDebit($data)
    {
        return $this->sendRequest('POST', '/api/debit', $data);
    }

    private function sendRequest($method, $endpoint, $data)
    {
        try {
            $response = $this->client->request($method, $endpoint, [
                'json' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // Handle request errors (e.g., log the error)
            return null;
        }
    }
}
