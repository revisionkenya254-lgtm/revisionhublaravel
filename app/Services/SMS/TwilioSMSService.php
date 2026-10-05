<?php

namespace App\Services\SMS;

use App\Contracts\SMSServiceInterface;
use Exception;
use Twilio\Rest\Client;

class TwilioSMSService implements SMSServiceInterface {
    public function send(string $to, string $message): bool {
        try {
            $setting = cache()->get('setting');
            $twilio = new Client($setting?->twilio_sid, $setting?->twilio_token);

            $response = $twilio->messages->create($to, [
                'from' => $setting?->twilio_from,
                'body' => $message,
            ]);

            // Optional: Log full response in local
            if (app()->isLocal()) {
                $data = [
                    'status'  => $response->status,
                    'sid'     => $response->sid,
                    'to'      => $response->to,
                    'from'    => $response->from,
                    'message' => $response->body,
                ];
                info(json_encode($data));
            }

            return true;

        } catch (Exception $e) {
            info('SMS Sending Failed: ' . $e->getMessage());
            return false;
        }
    }

}