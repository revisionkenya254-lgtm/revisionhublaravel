<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AIProvider;

interface AiProviderInterface
    extends AIProvider
{
    public function answer(array $messages, array $options = []): array;

    public function streamAnswer(array $messages, array $options, callable $onToken): array;
}

