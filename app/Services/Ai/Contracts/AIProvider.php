<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\DTO\ChatResponse;

interface AIProvider
{
    public function chat(ChatRequest $request): ChatResponse;

    public function stream(ChatRequest $request, callable $onToken): ChatResponse;

    public function embeddings(string|array $input): array;

    public function vision(ChatRequest $request): ChatResponse;

    public function generateImage(ChatRequest $request): array;

    public function health(): array;

    public function countTokens(string|array $input): int;
}

