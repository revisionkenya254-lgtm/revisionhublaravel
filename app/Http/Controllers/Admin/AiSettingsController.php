<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiRequest;
use App\Models\AiSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function index(): View
    {
        $settings = AiSetting::query()->first();
        $providers = AiProvider::query()->orderBy('priority')->orderBy('name')->get();

        $stats = [
            'request_count' => Schema::hasTable('ai_requests') ? AiRequest::count() : 0,
            'success_count' => Schema::hasTable('ai_requests') ? AiRequest::where('status', 'success')->count() : 0,
            'failed_count' => Schema::hasTable('ai_requests') ? AiRequest::where('status', 'failed')->count() : 0,
            'tokens' => Schema::hasTable('ai_requests') ? (int) AiRequest::sum('input_tokens') + (int) AiRequest::sum('output_tokens') : 0,
            'estimated_cost' => Schema::hasTable('ai_requests') ? (float) AiRequest::sum('estimated_cost') : 0,
        ];

        return view('admin.ai-settings.index', compact('settings', 'providers', 'stats'));
    }

    public function update(Request $request): JsonResponse
    {
        $providerKeys = array_keys((array) config('ai.providers', []));

        $validated = $request->validate([
            'default_provider' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, callable $fail) use ($providerKeys) {
                if (! in_array($value, $providerKeys, true)) {
                    $fail(__('The selected provider is not supported.'));
                }
            }],
            'fallback_provider' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, callable $fail) use ($providerKeys) {
                if (! in_array($value, $providerKeys, true)) {
                    $fail(__('The selected provider is not supported.'));
                }
            }],
            'routing_mode' => ['required', 'in:auto,openai_only'],
            'document_queue_mode' => ['required', 'in:local,redis'],
            'providers' => ['required', 'array'],
            'providers.*.enabled' => ['nullable', 'boolean'],
            'providers.*.priority' => ['nullable', 'integer', 'min:0'],
            'providers.*.default_model' => ['nullable', 'string', 'max:255'],
            'providers.*.model' => ['nullable', 'string', 'max:255'],
            'providers.*.timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'providers.*.base_url' => ['nullable', 'string', 'max:255'],
            'providers.*.status' => ['nullable', 'string', 'max:50'],
        ]);

        AiSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'default_provider' => $validated['default_provider'],
                'fallback_provider' => $validated['fallback_provider'],
                'routing_mode' => $validated['routing_mode'],
                'document_queue_mode' => $validated['document_queue_mode'],
            ]
        );

        foreach ($validated['providers'] as $name => $data) {
            AiProvider::query()->updateOrCreate(
                ['name' => $name],
                [
                    'enabled' => (bool) ($data['enabled'] ?? false),
                    'priority' => (int) ($data['priority'] ?? 0),
                    'default_model' => $data['default_model'] ?? null,
                    'model' => $data['model'] ?? null,
                    'timeout' => (int) ($data['timeout'] ?? 45),
                    'base_url' => $data['base_url'] ?? null,
                    'status' => $data['status'] ?? 'configured',
                    'is_default' => $name === $validated['default_provider'],
                    'is_fallback' => $name === $validated['fallback_provider'],
                ]
            );
        }

        $this->persistHealthCheckResults();

        return response()->json([
            'status' => 'success',
            'message' => __('AI settings updated successfully.'),
        ]);
    }

    public function healthCheck(): JsonResponse
    {
        $results = $this->persistHealthCheckResults();

        return response()->json([
            'status' => 'success',
            'providers' => $results,
        ]);
    }

    private function persistHealthCheckResults(): array
    {
        $results = [];

        foreach ((array) config('ai.providers', []) as $name => $definition) {
            $driver = $definition['driver'] ?? null;

            if (! is_string($driver) || ! class_exists($driver)) {
                $results[$name] = [
                    'provider' => $name,
                    'status' => 'unhealthy',
                    'message' => __('Provider driver is not configured.'),
                ];
                continue;
            }

            try {
                $results[$name] = app($driver)->health();
            } catch (\Throwable $throwable) {
                $results[$name] = [
                    'provider' => $name,
                    'status' => 'unhealthy',
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        foreach ($results as $name => $result) {
            AiProvider::query()->where('name', $name)->update([
                'status' => $result['status'] ?? 'unhealthy',
                'last_health_check_at' => now(),
                'last_health_status' => $result['status'] ?? 'unhealthy',
            ]);
        }

        return $results;
    }
}
