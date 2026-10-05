<?php

return [
    'core_api_key' => env('BUNNY_CORE_API_KEY', ''),

    'cdn_pull_zone_name' => env('BUNNY_CDN_PULL_ZONE_NAME', ''),
    'cdn_hostname' => env('BUNNY_CDN_HOSTNAME', ''),
    'cdn_status' => env('BUNNY_CDN_STATUS', 'inactive'),

    'storage_zone_name' => env('BUNNY_STORAGE_ZONE_NAME', ''),
    'storage_access_key' => env('BUNNY_STORAGE_ACCESS_KEY', ''),
    'storage_api_endpoint' => env('BUNNY_STORAGE_API_ENDPOINT', ''),
    'storage_cdn_url' => env('BUNNY_STORAGE_CDN_URL', ''),
    'storage_status' => env('BUNNY_STORAGE_STATUS', 'inactive'),

    'stream_library_id' => env('BUNNY_STREAM_LIBRARY_ID', ''),
    'stream_api_key' => env('BUNNY_STREAM_API_KEY', ''),
    'stream_cdn_hostname' => env('BUNNY_STREAM_CDN_HOSTNAME', ''),
    'stream_pull_zone_name' => env('BUNNY_STREAM_PULL_ZONE_NAME', ''),
    'stream_status' => env('BUNNY_STREAM_STATUS', 'inactive'),
];
