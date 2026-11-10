<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'test_id',
        'user_id',
        'score',
        'correct_count',
        'wrong_count',
        'skipped_count',
        'accuracy',
        'time_taken_seconds',
        'is_passed',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'correct_count' => 'integer',
            'wrong_count' => 'integer',
            'skipped_count' => 'integer',
            'accuracy' => 'decimal:2',
            'time_taken_seconds' => 'integer',
            'is_passed' => 'boolean',
            'evaluated_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
