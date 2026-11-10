<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps one row (an array) from LeaderboardService.
 */
class LeaderboardEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->resource['rank_no'],
            'user_id' => $this->resource['user_id'],
            'name' => $this->resource['name'],
            'score' => (float) $this->resource['score'],
            'time_taken_seconds' => $this->resource['time_taken_seconds'],
            'percentile' => (float) $this->resource['percentile'],
        ];
    }
}
