<?php

namespace App\Providers;

use App\Models\Course;
use App\Observers\CourseObserver;
use App\Observers\SettingObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\GlobalSetting\app\Models\Setting;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\TrackUserLogin',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'App\Listeners\TrackUserLogout',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register observers for cache invalidation
        Course::observe(CourseObserver::class);
        Setting::observe(SettingObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
