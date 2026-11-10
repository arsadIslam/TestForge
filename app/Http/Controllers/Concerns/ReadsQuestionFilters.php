<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\Difficulty;
use Illuminate\Http\Request;

/**
 * Reads the question-bank filters from the query string. Lenient on purpose:
 * a missing or invalid value (?difficulty=foo, ?topic_id=abc) just means "no filter".
 */
trait ReadsQuestionFilters
{
    /**
     * @return array{topic_id: ?int, difficulty: ?Difficulty, tag_id: ?int}
     */
    protected function questionFilters(Request $request): array
    {
        return [
            'topic_id' => $request->integer('topic_id') ?: null,
            'difficulty' => $request->enum('difficulty', Difficulty::class),
            'tag_id' => $request->integer('tag_id') ?: null,
        ];
    }
}
