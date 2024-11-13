<?php

class PaymentGateway {
    private $pdo;
    private $secretKey;

    public function __construct() {
       
    }

    /**
     * Creates a Checkout Session in PayMongo.
     *
     * @param int $amount The amount in cents (e.g., 10000 for 100 PHP).
     * @param string $currency The currency code (e.g., "PHP").
     * @param string $description Description of the payment.
     * @param string $successUrl URL to redirect on successful payment.
     * @param string $cancelUrl URL to redirect if the payment is canceled.
     * @return array The response from PayMongo API.
     */
    public function createCheckoutSession($amount, $currency = 'PHP', $description = 'Payment', $successUrl, $cancelUrl, $name) {
        $url = "https://api.paymongo.com/v1/checkout_sessions";
        
        $data = [
            'data' => [
                'attributes' => [
                    'line_items' => [
                        [
                            'name' => $name,
                            'amount' => $amount,
                            'currency' => $currency,
                            'description' => $description,
                            'quantity' => 1,
                        ]
                    ],
                    'payment_method_types' => ['card', 'paymaya', 'gcash'], 
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl
                ]
            ]
        ];

        $response = $this->makeRequest('POST', $url, $data);
        return json_decode($response, true);
    }

    /**
     * Helper function to make a cURL request.
     *
     * @param string $method The HTTP method (e.g., "POST").
     * @param string $url The API endpoint URL.
     * @param array $data The data to send in the request body.
     * @return string The raw response from cURL.
     */
    private function makeRequest($method, $url, $data) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic c2tfdGVzdF9QMVVNRG1VREJUYUhaaGppV0xVV3NyVHQ6c2tfdGVzdF9QMVVNRG1VREJUYUhaaGppV0xVV3NyVHQ=',
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Request Error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}
