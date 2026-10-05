<?php

namespace App\Listeners;

use App\Models\UserDevice;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class TrackUserLogout {
    public function handle(Logout $event) {
        if ($event->guard !== 'web') {
            return;
        }
        UserDevice::where('session_id', Session::getId())->delete();
    }
}
