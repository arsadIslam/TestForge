<?php

namespace App\Http\Resources;

use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Result
 */
class ResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attempt_id' => $this->attempt_id,
            'attempt_no' => $this->whenLoaded('attempt', fn () => $this->attempt->attempt_no),
            // whenLoaded(): included only if the controller eager-loaded it — never a lazy query.
            'test' => new TestResource($this->whenLoaded('test')),
            'score' => (float) $this->score,
            'correct_count' => $this->correct_count,
            'wrong_count' => $this->wrong_count,
            'skipped_count' => $this->skipped_count,
            'accuracy' => (float) $this->accuracy,
            'time_taken_seconds' => $this->time_taken_seconds,
            'is_passed' => $this->is_passed,
            'evaluated_at' => $this->evaluated_at->toIso8601String(),
        ];
    }
}
