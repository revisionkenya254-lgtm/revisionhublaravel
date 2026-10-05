<?php

use App\Enums\AiRoutingMode;

return [
    'default' => env('AI_DEFAULT', 'openai'),
    'fallback' => env('AI_FALLBACK', 'openai'),
    'routing_mode' => env('AI_ROUTING_MODE', AiRoutingMode::Auto->value),
    'document_processing' => [
        'default_mode' => env('AI_DOCUMENT_QUEUE_MODE', 'local'),
        'queue_name' => env('AI_DOCUMENT_QUEUE_NAME', 'ai-processing'),
        'local_connection' => env('AI_DOCUMENT_LOCAL_CONNECTION', 'database'),
        'redis_connection' => env('AI_DOCUMENT_REDIS_CONNECTION', 'redis'),
    ],
    'credits' => [
        'tokens_per_credit' => (int) env('AI_CREDITS_TOKENS_PER_CREDIT', 800),
        'default_monthly_allowance' => (int) env('AI_CREDITS_DEFAULT_MONTHLY_ALLOWANCE', 3000),
    ],
    'purchase' => [
        'credits_per_kes' => (int) env('AI_CREDITS_PURCHASE_RATE', 10),
        'minimum_amount' => (int) env('AI_CREDITS_MIN_PURCHASE_AMOUNT', 10),
    ],
    'history' => [
        'max_messages' => (int) env('AI_HISTORY_MAX_MESSAGES', 16),
    ],
    'providers' => [
        'openai' => [
            'label' => 'ChatGPT',
            'driver' => App\Services\Ai\Providers\OpenAIProvider::class,
            'enabled' => env('OPENAI_ENABLED', true),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-2'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 45),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'image_size' => env('OPENAI_IMAGE_SIZE', '1024x1024'),
            'image_quality' => env('OPENAI_IMAGE_QUALITY', 'auto'),
            'image_output_format' => env('OPENAI_IMAGE_OUTPUT_FORMAT', 'png'),
            'pricing' => [
                'input_per_1k' => (float) env('OPENAI_INPUT_COST_PER_1K', 0),
                'output_per_1k' => (float) env('OPENAI_OUTPUT_COST_PER_1K', 0),
            ],
        ],
    ],
    'routing' => [
        'rules' => [
            'essay' => [
                'keywords' => ['essay', 'write an essay', 'essay writing', 'argumentative essay'],
                'provider' => 'openai',
            ],
            'homework' => [
                'keywords' => ['homework', 'assignment', 'solve this', 'worksheet'],
                'provider' => 'openai',
            ],
            'math_reasoning' => [
                'keywords' => ['math', 'equation', 'proof', 'solve step by step', 'reasoning'],
                'provider' => 'openai',
            ],
            'science_reasoning' => [
                'keywords' => ['science', 'biology', 'chemistry', 'physics', 'explain the process'],
                'provider' => 'openai',
            ],
            'pdf' => [
                'keywords' => ['pdf', 'document', 'page', 'chapter', 'scan this file'],
                'provider' => 'openai',
            ],
            'image_analysis' => [
                'keywords' => ['image', 'screenshot', 'picture', 'diagram', 'chart', 'visual'],
                'provider' => 'openai',
            ],
            'general' => [
                'keywords' => ['hello', 'hi', 'explain', 'definition', 'simple', 'general'],
                'provider' => 'openai',
            ],
        ],
    ],
];

