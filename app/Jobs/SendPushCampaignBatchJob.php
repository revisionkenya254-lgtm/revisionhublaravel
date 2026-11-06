<?php

namespace App\Jobs;

use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationDelivery;
use App\Services\Push\FirebaseMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 80;

    public function __construct(
        public readonly int $campaignId,
        public readonly int $afterDeliveryId = 0,
    ) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(FirebaseMessagingService $messaging): void
    {
        $campaign = PushNotificationCampaign::query()->findOrFail($this->campaignId);
        if (in_array($campaign->status, ['sent', 'failed', 'no_recipients'], true)) {
            return;
        }

        $campaign->update(['status' => 'sending']);

        $deliveries = PushNotificationDelivery::query()
            ->with('deviceToken')
            ->where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->where('id', '>', $this->afterDeliveryId)
            ->orderBy('id')
            ->limit(5)
            ->get();

        foreach ($deliveries as $delivery) {
            $deviceToken = $delivery->deviceToken;
            if (! $deviceToken) {
                $delivery->update(['status' => 'failed', 'error_code' => 'device_token_missing']);

                continue;
            }

            $result = $messaging->send($deviceToken->token, $campaign);

            if ($result['status'] === 'unregistered') {
                $deviceToken->delete();
                $delivery->update(['status' => 'failed', 'error_code' => $result['error_code']]);

                continue;
            }

            $delivery->update([
                'status' => 'sent',
                'message_id' => $result['message_id'],
                'sent_at' => now(),
            ]);
        }

        $campaign->refresh();
        $campaign->sent_count = $campaign->deliveries()->where('status', 'sent')->count();
        $campaign->failed_count = $campaign->deliveries()->where('status', 'failed')->count();
        $campaign->save();

        $hasMore = PushNotificationDelivery::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasMore) {
            self::dispatch($campaign->id, $deliveries->last()?->id ?? $this->afterDeliveryId);

            return;
        }

        $campaign->update([
            'status' => $campaign->sent_count > 0 ? 'sent' : 'failed',
            'sent_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $campaign = PushNotificationCampaign::query()->find($this->campaignId);
        if (! $campaign || in_array($campaign->status, ['sent', 'no_recipients'], true)) {
            return;
        }

        $campaign->deliveries()
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error_code' => 'campaign_delivery_failed']);

        $campaign->update([
            'status' => 'failed',
            'sent_count' => $campaign->deliveries()->where('status', 'sent')->count(),
            'failed_count' => $campaign->deliveries()->where('status', 'failed')->count(),
            'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
