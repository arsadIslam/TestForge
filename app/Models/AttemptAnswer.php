<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'is_correct',
        'marks_awarded',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'marks_awarded' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * withTrashed(): a question soft-deleted after the attempt must still appear on the result page.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionOption::class,
            'attempt_answer_options',
            'attempt_answer_id',
            'question_option_id',
        );
    }
}
