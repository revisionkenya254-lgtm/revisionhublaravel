<?php

namespace App\Observers;

use App\Services\CacheService;
use Modules\GlobalSetting\app\Models\Setting;

class SettingObserver
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        // Clear settings cache when a new setting is created
        $this->cacheService->clearSettingsCache();
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        // Clear settings cache when a setting is updated
        $this->cacheService->clearSettingsCache();
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        // Clear settings cache when a setting is deleted
        $this->cacheService->clearSettingsCache();
    }
}