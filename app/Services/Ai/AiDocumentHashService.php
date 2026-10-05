<?php

namespace App\Services\Ai;

class AiDocumentHashService
{
    public function hashFile(string $path): string
    {
        return hash_file('sha256', $path);
    }
}
