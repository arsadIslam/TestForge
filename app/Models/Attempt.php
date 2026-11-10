<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attempt extends Model
{
    use HasFactory;

    /**
     * Attempts are only ever created by AttemptService with server-side values,
     * never filled straight from a request.
     */
    protected $fillable = [
        'test_id',
        'user_id',
        'attempt_no',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'attempt_no' => 'integer',
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * withTrashed(): an archived-then-deleted test must still show in a student's history.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }
}
