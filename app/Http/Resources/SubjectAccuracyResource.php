<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps one row (an array) from AnalyticsService::subjectAccuracy().
 */
class SubjectAccuracyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subject_id' => $this->resource['subject_id'],
            'subject' => $this->resource['subject'],
            'attempted' => $this->resource['attempted'],
            'correct' => $this->resource['correct'],
            'accuracy' => (float) $this->resource['accuracy'],
        ];
    }
}
