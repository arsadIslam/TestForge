<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use App\Enums\TestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Not fillable on purpose:
     * - created_by: set through $user->tests()->create(...)
     * - total_marks: calculated from the pivot marks, never typed in
     * - status / published_at: changed only by the publish/archive actions
     */
    protected $fillable = [
        'test_series_id', // null = free
        'title',
        'slug',
        'description',
        'duration_minutes',
        'pass_marks',
        'max_attempts',
        'shuffle_questions',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'total_marks' => 'decimal:2',
            'pass_marks' => 'decimal:2',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
            'status' => TestStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * The paid series this test belongs to; null means the test is free.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(TestSeries::class, 'test_series_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        // Table name is explicit: Laravel's alphabetical default would be "question_test".
        return $this->belongsToMany(Question::class, 'test_question')
            ->withPivot('sort_order', 'marks', 'negative_marks')
            ->orderByPivot('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Adds one student's attempt info to each test, without N+1:
     *  - my_attempts_count: a correlated COUNT sub-select (withCount with a constraint + alias)
     *  - attempts: only that student's in-progress attempt, eager loaded (for "Resume")
     */
    public function scopeWithAttemptInfoFor(Builder $query, int $studentId): void
    {
        $query
            ->withCount([
                'questions',
                'attempts as my_attempts_count' => fn (Builder $q) => $q->where('user_id', $studentId),
            ])
            ->with(['attempts' => fn (HasMany $q) => $q
                ->where('user_id', $studentId)
                ->where('status', AttemptStatus::InProgress),
            ]);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', TestStatus::Published);
    }

    /**
     * Inside the availability window. A NULL bound means "no limit on that side".
     * Chain it with published(): Test::published()->available().
     */
    public function scopeAvailable(Builder $query): void
    {
        $now = now();

        $query->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
