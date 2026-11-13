<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Test;
use App\Models\TestSeries;
use App\Models\User;
use App\Support\Access;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * Who may start which test.
 *
 *   - a test with no series is free;
 *   - a series test needs an active entitlement for that series, or an All-Access pass;
 *   - access is checked when an attempt STARTS (AttemptService), so an attempt already in
 *     progress can always be finished even if the access ends meanwhile.
 */
class AccessService
{
    /**
     * Everything the student can access right now, in ONE query — reuse it for a whole list.
     */
    public function accessFor(User $user): Access
    {
        $active = Entitlement::query()
            ->where('user_id', $user->id)
            ->active()
            ->get(['test_series_id', 'ends_at']);

        $allAccessUntil = null;
        $seriesUntil = [];

        foreach ($active as $entitlement) {
            if ($entitlement->test_series_id === null) {
                $allAccessUntil = $this->later($allAccessUntil, $entitlement->ends_at);
            } else {
                $id = $entitlement->test_series_id;
                // lifetime (null) beats any date
                $seriesUntil[$id] = array_key_exists($id, $seriesUntil) && $seriesUntil[$id] === null
                    ? null
                    : ($entitlement->ends_at === null ? null : $this->later($seriesUntil[$id] ?? null, $entitlement->ends_at));
            }
        }

        return new Access($allAccessUntil, $seriesUntil);
    }

    public function canStart(User $user, Test $test): bool
    {
        return $test->test_series_id === null || $this->accessFor($user)->canAccessSeries($test->test_series_id);
    }

    /**
     * Turn a PAID payment into access. Must run inside the caller's transaction (PaymentService).
     *
     * Buying again while still covered STACKS: the new period starts when the latest current one
     * ends. The student's row is locked so two payments finishing at the same moment can't both
     * compute the same start date. payment_id is unique on entitlements, so a payment can only
     * ever grant once.
     */
    public function grant(Payment $payment): Entitlement
    {
        User::query()->whereKey($payment->user_id)->lockForUpdate()->first();

        $payable = $payment->payable;
        [$seriesId, $days] = match (true) {
            $payable instanceof TestSeries => [$payable->id, $payable->validity_days], // null days = lifetime
            $payable instanceof Plan => [null, $payable->duration_days],
            default => throw new LogicException("Payment {$payment->id} has nothing to grant."),
        };

        $now = CarbonImmutable::now();

        $latestEnd = Entitlement::query()
            ->where('user_id', $payment->user_id)
            ->where('test_series_id', $seriesId) // null → whereNull: All-Access passes stack with each other
            ->notExpired()
            ->max('ends_at');

        $startsAt = $latestEnd !== null && $days !== null ? CarbonImmutable::parse($latestEnd)->max($now) : $now;

        return Entitlement::forceCreate([
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'test_series_id' => $seriesId,
            'starts_at' => $startsAt,
            'ends_at' => $days === null ? null : $startsAt->addDays($days),
        ]);
    }

    private function later(mixed $a, mixed $b): mixed
    {
        if ($a === null) {
            return $b;
        }

        return $b !== null && $b->greaterThan($a) ? $b : $a;
    }
}
