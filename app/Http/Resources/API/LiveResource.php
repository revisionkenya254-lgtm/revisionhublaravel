<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $title = data_get($this->resource, 'title');
        $description = data_get($this->resource, 'description');
        $startTime = data_get($this->resource, 'start_time');
        $endTime = data_get($this->resource, 'end_time');
        $duration = data_get($this->resource, 'duration');
        $isLiveNow = data_get($this->resource, 'is_live_now');
        $live = data_get($this->resource, 'live');

        return [
            'id'          => (int) $id,
            'title'       => (string) $title,
            'description' => (string) $description,
            'start_time'  => (string) $startTime,
            'end_time'    => (string) $endTime,
            'duration'    => (string) convertMinutesToHoursAndMinutes($duration),
            'is_live_now' => (string) $isLiveNow,
            'type'        => (string) data_get($live, 'type'),
            'meeting_id'  => (string) data_get($live, 'meeting_id'),
            'password'    => (string) data_get($live, 'password'),
            'join_url'    => (string) data_get($live, 'join_url'),
        ];
    }
}
