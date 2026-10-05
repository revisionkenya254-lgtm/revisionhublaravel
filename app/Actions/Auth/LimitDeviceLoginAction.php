<?php

namespace App\Actions\Auth;

use App\Services\DeviceService;
use Illuminate\Support\Facades\Config;

class LimitDeviceLoginAction {
    protected DeviceService $deviceService;

    public function __construct(DeviceService $deviceService) {
        $this->deviceService = $deviceService;
    }

    public function handle(int $userId, string $sessionId, string $deviceType = 'web'): void {
        // Save or update current device record
        $this->deviceService->store($userId, $sessionId, $deviceType);

        // Count active devices
        $activeDevices = $this->deviceService->allActiveDevices($userId);
        $maxDevices = Config::get('auth.max_devices', 5);

        // If user exceeded max devices, remove oldest
        if ($activeDevices->count() > $maxDevices) {
            $exceed = $activeDevices->count() - $maxDevices;
            $oldDevices = $activeDevices->take($exceed);

            foreach ($oldDevices as $device) {
                $this->deviceService->destroy($device);
            }
        }
    }
}
