<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SabbathPlanService;
use Illuminate\View\View;

class SabbathPlanController extends Controller
{
    public function index(SabbathPlanService $sabbathPlan): View
    {
        $plan = $sabbathPlan->plan();
        $active = $sabbathPlan->isActive();
        $window = $sabbathPlan->currentOrNextWindow();
        $now = now($plan['timezone']);

        return view('admin.sabbath-plan.index', compact('plan', 'active', 'window', 'now'));
    }
}
