<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PreparePushCampaignJob;
use App\Models\PushNotificationCampaign;
use App\Services\Push\FirebaseMessagingService;
use App\Services\Push\PushAudience;
use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PushNotificationController extends Controller
{
    public function index(): View
    {
        $this->authorizePushManagement();

        $campaigns = PushNotificationCampaign::query()
            ->with('admin:id,name')
            ->latest('id')
            ->paginate(15);

        return view('admin.push-notifications.index', [
            'campaigns' => $campaigns,
            'isConfigured' => app(FirebaseMessagingService::class)->isConfigured(),
        ]);
    }

    public function create(): View
    {
        $this->authorizePushManagement();

        return view('admin.push-notifications.create', [
            'educationLevels' => app(TaxonomyCatalogRepository::class)->educationLevels(),
            'audienceCounts' => app(PushAudience::class)->countsByEducationLevel(),
            'isConfigured' => app(FirebaseMessagingService::class)->isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePushManagement();

        $firebase = app(FirebaseMessagingService::class);
        if (! $firebase->isConfigured()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['firebase' => __('Push delivery is not configured. Contact your system administrator.')]);
        }

        $educationLevelSlugs = collect(app(TaxonomyCatalogRepository::class)->educationLevels())
            ->pluck('slug')
            ->filter()
            ->all();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'target_type' => ['required', Rule::in(['all', 'education_level'])],
            'education_level' => [
                Rule::requiredIf($request->input('target_type') === 'education_level'),
                'nullable',
                'string',
                Rule::in($educationLevelSlugs),
            ],
            'target_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        $campaign = PushNotificationCampaign::query()->create([
            'admin_id' => auth('admin')->id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'target_type' => $validated['target_type'],
            'education_level' => $validated['target_type'] === 'education_level'
                ? $validated['education_level']
                : null,
            'target_url' => $validated['target_url'] ?? null,
            'status' => 'preparing',
        ]);

        PreparePushCampaignJob::dispatch($campaign->id)->afterCommit();

        return redirect()->route('admin.push-notifications.index')
            ->with('messege', __('Campaign queued for delivery.'))
            ->with('alert-type', 'success');
    }

    private function authorizePushManagement(): void
    {
        checkAdminHasPermissionAndThrowException('customer.view');
    }
}
