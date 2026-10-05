<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceService {

    public function allActiveDevices(int $userId): Collection {
        return UserDevice::where('user_id', $userId)->orderBy('created_at')->get();
    }
    public function store(int $userId, string $sessionId, string $deviceType = 'web'): ?UserDevice {
        $device = UserDevice::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id'     => $userId,
                'ip_address'  => request()->ip(),
                'user_agent'  => substr(request()->userAgent(), 0, 255),
                'device_type' => $deviceType,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        );

        return $device;
    }
    public function destroy(UserDevice $device): void {
        $this->deleteTokenOrSessionId($device);
        $device->delete();
    }
    public function destroyAll(): void {
        $currentSession = session()->getId();

        $devices = UserDevice::where('user_id', Auth::id())->where('session_id', '!=', $currentSession)->get();
        $ids = $devices->pluck('id');

        foreach ($devices as $device) {
            $this->deleteTokenOrSessionId($device);
        }
        UserDevice::whereIn('id', $ids)->delete();

    }

    private function deleteTokenOrSessionId(UserDevice $device): void {
        if ($device->device_type == 'web') {
            $path = storage_path("framework/sessions/{$device->session_id}");
            if (File::exists($path)) {
                File::delete($path);
            }
        } else {
            // Delete Sanctum token (hashed)
            if (str_contains($device->session_id, '|')) {
                $parts = explode('|', $device->session_id, 2);
                $hashedToken = hash('sha256', $parts[1]);
                PersonalAccessToken::where('token', $hashedToken)->delete();
            }
        }
    }

}