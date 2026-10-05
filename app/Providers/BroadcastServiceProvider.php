<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->pusherConfig();
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
    private function pusherConfig(): void
    {
        $setting = Cache::get('setting');
        if ($setting && (($setting->pusher_status ?? null) === 'active')) {
            config(['broadcasting.connections.pusher.key' => $setting?->pusher_app_key]);
            config(['broadcasting.connections.pusher.secret' => $setting?->pusher_app_secret]);
            config(['broadcasting.connections.pusher.app_id' => $setting?->pusher_app_id]);
            config(['broadcasting.connections.pusher.options.cluster' => $setting?->pusher_app_cluster]);
            config(['broadcasting.connections.pusher.options.host' => 'api-' . $setting?->pusher_app_cluster . '.pusher.com']);
        }
    }
}
