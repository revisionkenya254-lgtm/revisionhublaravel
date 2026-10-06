<?php

namespace App\Providers;

use App\Enums\ThemeList;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Services\StudentDashboardSidebarService;
use Modules\GlobalSetting\app\Models\MarketingSetting;
use Modules\GlobalSetting\app\Models\SeoSetting;
use Modules\GlobalSetting\app\Models\Setting;

class SettingServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $defaultSetting = $this->defaultSetting();
        $setting = new Fluent($defaultSetting);
        $marketing_setting = (object) [];
        $seo_setting = (object) [];

        try {
            if (Schema::hasTable('settings')) {
                $setting = Cache::rememberForever('setting', fn() => new Fluent(array_merge(
                    $defaultSetting,
                    Setting::pluck('value', 'key')->all()
                )));
            }

            if (Schema::hasTable('marketing_settings')) {
                $marketing_setting = Cache::rememberForever('marketing_setting', fn() => (object) MarketingSetting::pluck('value', 'key')->all());
            }

            if (Schema::hasTable('seo_settings')) {
                $seo_setting = Cache::rememberForever('seo_setting', fn() => (object) SeoSetting::all()->groupBy('page_name')->mapWithKeys(function ($group, $pageName) {
                    return [$pageName => $group->first()];
                }));
            }

            if ($setting) {
                set_wasabi_config();
                set_bunny_config();
                set_aws_config();
                config(['auth.max_devices' => data_get($setting, 'max_login_devices')]);
            }
        } catch (\Throwable $th) {
            info($th);
            $setting = new Fluent($defaultSetting);
            $marketing_setting = (object) [];
            $seo_setting = (object) [];
        }

        /** Share settings to all views */
        View::composer('*', function ($view) use ($setting, $marketing_setting, $seo_setting) {
            $view->with(['setting' => $setting, 'marketing_setting' => $marketing_setting, 'seo_setting' => $seo_setting]);
        });

View::composer('frontend.student-dashboard.layouts.sidebar', function ($view) {            $view->with([
                'studentSidebarSections' => app(StudentDashboardSidebarService::class)->sidebarSections(),
            ]);
        });

        // Set timezone
        date_default_timezone_set($setting->timezone ?? config('app.timezone'));

        // Define default homepage based on site_theme from setting, with fallback
        if (!defined('DEFAULT_HOMEPAGE')) {
            define('DEFAULT_HOMEPAGE', $setting?->site_theme ?? ThemeList::MAIN->value);
        }
    }

    private function defaultSetting(): array
    {
        return [
            'app_name' => 'RevisionHubKenya',
            'timezone' => config('app.timezone'),
            'site_theme' => ThemeList::MAIN->value,
            'version' => '3.4.0',
            'favicon' => 'uploads/website-images/favicon.png',
            'logo' => 'uploads/website-images/logo.svg',
            'preloader' => '/frontend/img/logo/preloader.svg',
            'preloader_status' => 1,
            'primary_color' => '#5751e1',
            'secondary_color' => '#ffc224',
            'common_color_one' => '#050071',
            'common_color_two' => '#282568',
            'common_color_three' => '#1C1A4A',
            'common_color_four' => '#06042E',
            'common_color_five' => '#4a44d1',
            'header_topbar_status' => 'active',
            'header_social_status' => 'active',
            'cursor_dot_status' => 'inactive',
            'google_tagmanager_status' => 'inactive',
            'google_tagmanager_id' => '',
            'recaptcha_status' => 'inactive',
            'tawk_status' => 'inactive',
            'cookie_status' => 'inactive',
            'bunny_core_api_key' => '',
            'bunny_cdn_pull_zone_name' => '',
            'bunny_cdn_hostname' => '',
            'bunny_cdn_status' => 'inactive',
            'bunny_storage_zone_name' => '',
            'bunny_storage_access_key' => '',
            'bunny_storage_api_endpoint' => '',
            'bunny_storage_cdn_url' => '',
            'bunny_storage_status' => 'inactive',
            'bunny_stream_library_id' => '',
            'bunny_stream_api_key' => '',
            'bunny_stream_cdn_hostname' => '',
            'bunny_stream_pull_zone_name' => '',
            'bunny_stream_status' => 'inactive',
            'bunny_connection_test_core' => '',
            'bunny_connection_test_cdn' => '',
            'bunny_connection_test_storage' => '',
            'bunny_connection_test_storage_public' => '',
            'bunny_connection_test_stream' => '',
            'copyright_text' => '',
            'maintenance_mode' => 0,
        ];
    }
}
