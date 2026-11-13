<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A paid group of tests (e.g. SSC, WBSC). Tests without a series are free.
 */
class TestSeries extends Model
{
    use HasFactory;

    protected $table = 'test_series';

    protected $fillable = ['name', 'slug', 'description', 'price_paise', 'validity_days', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_paise' => 'integer',
            'validity_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
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

    protected function validityLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->validity_days ? "{$this->validity_days} days" : 'Lifetime');
    }
}
