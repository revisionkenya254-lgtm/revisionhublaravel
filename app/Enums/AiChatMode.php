<?php

namespace App\Enums;

enum AiChatMode: string
{
    case General = 'general';
    case SimpleExplanation = 'simple_explanation';
    case Definition = 'definition';
    case Essay = 'essay';
    case Homework = 'homework';
    case MathReasoning = 'math_reasoning';
    case ScienceReasoning = 'science_reasoning';
    case Pdf = 'pdf';
    case ImageAnalysis = 'image_analysis';
}
