<?php

return [
    /*
    | Log requests whose cumulative database work crosses this threshold.
    | This uses Laravel's built-in query monitor and has no paid dependency.
    */
    'slow_database_request_ms' => (int) env('SLOW_DATABASE_REQUEST_MS', 200),
];
