<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

class AIException extends RuntimeException
{
    public static function providerUnavailable(string $message = 'The AI provider is temporarily unavailable.'): self
    {
        return new self($message);
    }

    public static function providerDisabled(string $provider): self
    {
        return new self("The {$provider} provider is disabled.");
    }

    public static function unsupportedFeature(string $feature, string $provider): self
    {
        return new self("The {$provider} provider does not support {$feature}.");
    }
}

