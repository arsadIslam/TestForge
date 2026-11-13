<?php

namespace App\Http\Resources;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Test
 */
class TestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'total_marks' => (float) $this->total_marks,
            'pass_marks' => (float) $this->pass_marks,
            'max_attempts' => $this->max_attempts,
            // whenCounted(): only present when the query used withCount(), never an extra query.
            'questions_count' => $this->whenCounted('questions'),
            'my_attempts_count' => $this->whenCounted('my_attempts'),
            'attempts_left' => $this->when(
                isset($this->my_attempts_count),
                fn (): int => max(0, $this->max_attempts - $this->my_attempts_count),
            ),
            'series' => $this->whenLoaded('series', fn () => $this->series ? [
                'id' => $this->series->id,
                'name' => $this->series->name,
                'slug' => $this->series->slug,
                'price_paise' => $this->series->price_paise,
            ] : null),
            // true = paid series the student hasn't bought (and no All-Access pass)
            'is_locked' => $this->when(
                $request->attributes->has('access'),
                fn (): bool => ! $request->attributes->get('access')->canAccessSeries($this->test_series_id),
            ),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
        ];
    }
}
