<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SendSms
{
    protected $client;

    public function __construct()
    {
        // Use Laravel config to retrieve Twilio credentials
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function send($to, $message)
    {
        try {
            $this->client->messages->create(
                $to,
                [
                    'from' => config('services.twilio.phone_number'),
                    'body' => $message
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
}

