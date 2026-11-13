<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An All-Access pass: one payment unlocks every test for duration_days.
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price_paise', 'duration_days', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_paise' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected function price(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->price_paise));
    }
}
