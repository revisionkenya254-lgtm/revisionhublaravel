<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogCacheClear
{
    public const VERSION_KEY = 'catalog_cache_version';

    public static function clear(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }

    public static function version(): string
    {
        return (string) Cache::get(self::VERSION_KEY, '1');
    }
}
