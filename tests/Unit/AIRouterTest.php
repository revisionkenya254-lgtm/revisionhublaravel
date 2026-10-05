<?php

namespace Tests\Unit;

use App\Services\Ai\DTO\ChatRequest;
use App\Services\Ai\Router\AIRouter;
use Tests\TestCase;

class AIRouterTest extends TestCase
{
    public function test_it_routes_essay_prompts_to_openai_first(): void
    {
        config()->set('ai.routing_mode', 'auto');

        $router = app(AIRouter::class);

        $providers = $router->route(ChatRequest::fromArray([
            'prompt' => 'Please write an essay about climate change.',
            'mode' => 'essay',
            'provider' => 'auto',
        ]));

        $this->assertSame('openai', $providers[0]);
    }

    public function test_it_routes_simple_explanations_to_chatgpt_first(): void
    {
        config()->set('ai.routing_mode', 'auto');

        $router = app(AIRouter::class);

        $providers = $router->route(ChatRequest::fromArray([
            'prompt' => 'Give me a simple explanation of photosynthesis.',
            'mode' => 'general',
            'provider' => 'auto',
        ]));

        $this->assertSame('openai', $providers[0]);
    }
}

