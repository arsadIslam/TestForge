<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Nothing is mass-assignable: payments are only written by PaymentService (forceFill),
 * never from request input — the amount in particular always comes from the database.
 */
class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What was bought: a TestSeries or a Plan (morph map in AppServiceProvider).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function entitlement(): HasOne
    {
        return $this->hasOne(Entitlement::class);
    }

    protected function amount(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->amount_paise));
    }
}
