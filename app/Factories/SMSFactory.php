<?php

namespace App\Factories;

use App\Contracts\SMSServiceInterface;
use App\Services\SMS\TwilioSMSService;
use InvalidArgumentException;

class SMSFactory {
    public static function make(string $driver): SMSServiceInterface {
        return match ($driver) {
            'twilio' => new TwilioSMSService(),
            default => throw new InvalidArgumentException("Unsupported SMS driver [$driver]"),
        };
    }
}