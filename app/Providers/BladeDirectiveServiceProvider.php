<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeDirectiveServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Blade::directive('adminCan', function ($permission) {
            return "<?php if(auth()->guard('admin')->user()?->can({$permission})): ?>";
        });

        Blade::directive('endadminCan', function () {
            return '<?php endif; ?>';
        });

        // Blade directive for checking the current theme
        Blade::directive('theme', function ($themes) {
            return "<?php if(in_array(DEFAULT_HOMEPAGE, {$themes})): ?>";
        });

        Blade::directive('endtheme', function () {
            return '<?php endif; ?>';
        });
    }
}
