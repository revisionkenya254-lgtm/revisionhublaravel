<?php

return [
    'trial_days' => max(1, (int) env('SUBSCRIPTION_TRIAL_DAYS', 7)),
];
