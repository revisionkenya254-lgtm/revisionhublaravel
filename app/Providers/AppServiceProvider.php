<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Use Bootstrap 4 pagination
        Paginator::useBootstrapFour();
        Model::preventLazyLoading(! app()->isProduction());

        if (! app()->runningInConsole()) {
            DB::whenQueryingForLongerThan(
                (int) config('performance.slow_database_request_ms', 200),
                function (Connection $connection, QueryExecuted $query): void {
                    Log::warning('Slow cumulative database time detected', [
                        'method' => request()->method(),
                        'path' => request()->path(),
                        'route' => request()->route()?->getName(),
                        'database' => $connection->getDatabaseName(),
                        'query_ms' => $query->time,
                        'sql' => $query->sql,
                    ]);
                }
            );
        }

        if (! app()->runningInConsole()) {
            $request = request();
            URL::forceRootUrl(rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/'));
        }
    }
}
