<?php

namespace App\Listeners;

use App\Actions\Auth\LimitDeviceLoginAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;

class TrackUserLogin {
    public function handle(Login $event) {
        if ($event->guard !== 'web') {
            return;
        }
        $user = $event->user;
        $sessionId = Session::getId();

        // Delegate to action
        app(LimitDeviceLoginAction::class)->handle($user->id, $sessionId, 'web');
    }
}
