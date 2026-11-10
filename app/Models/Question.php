<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Enums\TestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * created_by is deliberately not fillable: it is set from the logged-in teacher
     * via $user->questions()->create(...), never from request input.
     */
    protected $fillable = [
        'topic_id',
        'type',
        'body',
        'explanation',
        'difficulty',
        'marks',
        'negative_marks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => Difficulty::class,
            'marks' => 'decimal:2',
            'negative_marks' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function tests(): BelongsToMany
    {
        // Table name is explicit: Laravel's alphabetical default would be "question_test".
        return $this->belongsToMany(Test::class, 'test_question')
            ->withPivot('sort_order', 'marks', 'negative_marks');
    }

    /**
     * A question used in a published or archived test has a frozen answer key
     * (type, options, which options are correct): students may already have
     * answered it, and attempt_answer_options points at these exact option rows.
     * Tests only move draft → published → archived, so once locked, always locked.
     */
    public function isLocked(): bool
    {
        return $this->tests()->where('tests.status', '!=', TestStatus::Draft)->exists();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
