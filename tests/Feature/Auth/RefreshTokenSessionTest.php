<?php

namespace Tests\Feature\Auth;

use App\Models\DeviceSession;
use App\Models\User;
use App\Services\TokenSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RefreshTokenSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_rotation_replaces_the_token_and_reuse_revokes_its_family(): void
    {
        $user = User::factory()->create();
        $service = app(TokenSessionService::class);
        $request = Request::create('/api/auth/google', 'POST', [
            'device_installation_id' => 'install-1',
            'device_name' => 'Pixel test device',
        ]);

        $first = $service->createSession($user, $request);
        $second = $service->rotate($first['refresh_token'], $request);

        $this->assertNotNull($second);
        $this->assertNotSame($first['refresh_token'], $second['refresh_token']);
        $this->assertNull($service->rotate($first['refresh_token'], $request));
        $this->assertNotNull(DeviceSession::find($first['session_id'])->revoked_at);
        $this->assertNull($service->rotate($second['refresh_token'], $request));
    }

    public function test_device_limit_evicts_the_least_recent_mobile_session(): void
    {
        config(['auth.max_devices' => 1]);
        $user = User::factory()->create();
        $service = app(TokenSessionService::class);

        $first = $service->createSession($user, Request::create('/', 'POST', ['device_installation_id' => 'one']));
        $second = $service->createSession($user, Request::create('/', 'POST', ['device_installation_id' => 'two']));

        $this->assertNotNull(DeviceSession::find($first['session_id'])->revoked_at);
        $this->assertNull(DeviceSession::find($second['session_id'])->revoked_at);
    }
}
