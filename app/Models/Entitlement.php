<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Access a student has bought: one series (test_series_id) or everything (test_series_id = null),
 * from starts_at until ends_at (null = lifetime). Created only by AccessService::grant().
 */
class Entitlement extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(TestSeries::class, 'test_series_id');
    }

    /**
     * Valid right now.
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('starts_at', '<=', $now)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    /**
     * Valid now OR starting later (a stacked purchase).
     */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
