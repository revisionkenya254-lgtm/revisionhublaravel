<?php

return [
    /*
    | The Sabbath plan is an internal, zero-cost catalogue entitlement. It is
    | intentionally kept separate from the public, purchasable plan list.
    */
    'enabled' => (bool) env('SABBATH_PLAN_ENABLED', true),
    'id' => 'sabbath',
    'name' => 'Sabbath Plan',
    'timezone' => env('SABBATH_PLAN_TIMEZONE', 'Africa/Nairobi'),
    'starts' => [
        'day' => 5, // ISO-8601 Friday
        'time' => '18:00',
    ],
    'ends' => [
        'day' => 6, // ISO-8601 Saturday
        'time' => '19:00',
    ],
];
