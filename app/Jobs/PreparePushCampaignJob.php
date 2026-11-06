<?php

namespace App\Jobs;

use App\Models\PushDeviceToken;
use App\Models\PushNotificationCampaign;
use App\Services\Push\PushAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PreparePushCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $campaignId) {}

    public function handle(PushAudience $audience): void
    {
        $campaign = PushNotificationCampaign::query()->findOrFail($this->campaignId);
        if ($campaign->status !== 'preparing') {
            return;
        }

        $query = $audience->eligibleTokens(
            $campaign->target_type === 'education_level' ? $campaign->education_level : null
        );

        $query->select('push_device_tokens.id', 'push_device_tokens.token_hash')
            ->orderBy('push_device_tokens.id')
            ->chunkById(500, function ($tokens) use ($campaign): void {
                $now = now();
                DB::table('push_notification_deliveries')->insertOrIgnore(
                    $tokens->map(fn (PushDeviceToken $token) => [
                        'campaign_id' => $campaign->id,
                        'push_device_token_id' => $token->id,
                        'token_hash' => $token->token_hash,
                        'status' => 'pending',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        $campaign->recipient_count = $campaign->deliveries()->count();
        if ($campaign->recipient_count === 0) {
            $campaign->status = 'no_recipients';
            $campaign->sent_at = now();
            $campaign->save();

            return;
        }

        $campaign->status = 'sending';
        $campaign->save();
        SendPushCampaignBatchJob::dispatch($campaign->id);
    }

    public function failed(\Throwable $exception): void
    {
        $campaign = PushNotificationCampaign::query()->find($this->campaignId);
        if (! $campaign) {
            return;
        }

        $campaign->deliveries()
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error_code' => 'campaign_preparation_failed']);
        $campaign->update([
            'status' => 'failed',
            'recipient_count' => $campaign->deliveries()->count(),
            'failed_count' => $campaign->deliveries()->where('status', 'failed')->count(),
            'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
