<?php

class SmsGateway {
    function sendSmsMessage($message,$phoneNumbers) {
        // Define the API URL
        $apiUrl = 'http://192.168.1.2:8080/message';
    
        // Prepare the data to send in the POST request
        $data = [
            'message' => $message,
            'phoneNumbers' => $phoneNumbers // Ensure this is an array
        ];
    
        // Initialize cURL session
        $ch = curl_init($apiUrl);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('sms:zrzME7Tm') 
        ]);
    
        // Execute the cURL request and capture the response
        $response = curl_exec($ch);
    
        // Check for errors in the cURL request
        if (curl_errno($ch)) {
            // If an error occurs, throw an exception
            throw new Exception('Error sending request to the API: ' . curl_error($ch));
        }
    
        // Close the cURL session
        curl_close($ch);
    
        // Decode the response and return the result
        $responseData = json_decode($response, true);
        return $responseData;
    }
}
