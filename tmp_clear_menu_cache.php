<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$svc = app(App\Services\MenuCacheService::class);
$svc->clearMenuCache();
$cache = Illuminate\Support\Facades\Cache::getFacadeRoot();
foreach (['en', 'sw', config('app.locale'), function_exists('getSessionLanguage') ? getSessionLanguage() : null] as $language) {
    if (blank($language)) {
        continue;
    }
    foreach (['v8', 'v9'] as $version) {
        Cache::forget('menu_category_links_'.$version.'_'.$language);
        Cache::forget('menu_category_tree_'.$version.'_'.$language);
        Cache::forget('menu_structure_'.$version.'_'.$language);
        Cache::forget('mobile_menu_'.$version.'_'.$language);
        Cache::forget('main_categories_'.$version.'_'.$language);
    }
}
echo "menu cache cleared\n";
