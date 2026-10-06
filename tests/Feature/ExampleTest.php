<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_production_domain_is_configured(): void
    {
        $this->assertSame('https://revisionhubkenya.com', config('app.url'));
    }
}
