<?php

namespace App\Http\Controllers\Frontend;

use App\Models\UserDevice;
use App\Services\DeviceService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceController extends Controller {
    private $service;
    public function __construct(DeviceService $service){
        $this->service = $service;
    }
    // Show all active devices
    public function index() {
        $devices = UserDevice::where('user_id', Auth::id())->orderByDesc('created_at')->paginate(10);
        return view('frontend.student-dashboard.devices.index', compact('devices'));
    }

    // Logout from one specific device
    public function destroy($sessionId) {
        $device = UserDevice::where('user_id', Auth::id())->where('session_id', $sessionId)->first();
        if ($device) {
            $this->service->destroy($device);
        }
        return redirect()->back()->with(['alert-type' =>'success','messege' => __('Device logged out successfully.')]);
    }

    // Logout from all devices except current one
    public function destroyAll() {
        $this->service->destroyAll();
        return redirect()->back()->with(['alert-type' =>'success','messege' => __('Logged out from all other devices.')]);
    }
}
