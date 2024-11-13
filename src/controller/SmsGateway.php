<?php

require_once 'vendor/autoload.php'; // Path to Composer autoload file

use Twilio\Rest\Client;

class SmsGateway {
    private $client;
    private $twilioNumber;

    public function __construct($sid, $token, $twilioNumber) {
        $this->client = new Client($sid, $token);
        $this->twilioNumber = $twilioNumber;
    }

    public function sendSms($to, $message) {
        try {
            $message = $this->client->messages->create(
                $to, // Recipient's phone number
                [
                    'from' => $this->twilioNumber,
                    'body' => $message
                ]
            );
            echo "Message sent! SID: " . $message->sid;
        } catch (Exception $e) {
            echo "Failed to send SMS: " . $e->getMessage();
        }
    }
}

