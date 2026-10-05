<?php

namespace App\Contracts;

interface SMSServiceInterface {
    public function send(string $to, string $message): bool;
}