<?php

namespace App\Enums;

enum AiRoutingMode: string
{
    case OpenAiOnly = 'openai_only';
    case Auto = 'auto';
}
