<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Attempt;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Test;
use App\Models\TestSeries;
use App\Services\AccessService;
use Illuminate\Database\Seeder;

/**
 * Paid series + All-Access passes:
 *   SSC  (₹299, lifetime)  ← Mathematics Mock Test 1
 *   WBSC (₹499, 365 days)  ← Science Combined Test
 *   Computer Science Quiz stays FREE.
 * Students who already have attempts in a paid test get a matching paid purchase
 * (gateway "seed"), so the demo data never shows someone using a test they didn't buy.
 */
class SeriesSeeder extends Seeder
{
    public function run(AccessService $access): void
    {
        $ssc = TestSeries::create([
            'name' => 'SSC', 'slug' => 'ssc', 'price_paise' => 29900, 'validity_days' => null,
            'description' => 'Staff Selection Commission practice: quantitative aptitude mock tests.',
        ]);
        $wbsc = TestSeries::create([
            'name' => 'WBSC', 'slug' => 'wbsc', 'price_paise' => 49900, 'validity_days' => 365,
            'description' => 'West Bengal Service Commission practice: general science mock tests.',
        ]);

        Plan::create(['name' => 'All Access · 30 days', 'price_paise' => 19900, 'duration_days' => 30]);
        Plan::create(['name' => 'All Access · 365 days', 'price_paise' => 99900, 'duration_days' => 365]);

        $tests = [
            'mathematics-mock-test-1' => $ssc,
            'science-combined-test' => $wbsc,
        ];

        foreach ($tests as $slug => $series) {
            $test = Test::where('slug', $slug)->firstOrFail();
            $test->update(['test_series_id' => $series->id]);

            // Everyone who took it "bought" the series a day before their first attempt.
            $firstAttempts = Attempt::query()
                ->where('test_id', $test->id)
                ->selectRaw('user_id, MIN(started_at) AS first_started_at')
                ->groupBy('user_id')
                ->get();

            foreach ($firstAttempts as $row) {
                $paidAt = now()->parse($row->first_started_at)->subDay();

                $payment = new Payment;
                $payment->forceFill([
                    'user_id' => $row->user_id,
                    'payable_type' => $series->getMorphClass(),
                    'payable_id' => $series->id,
                    'amount_paise' => $series->price_paise,
                    'gateway' => 'seed',
                    'status' => PaymentStatus::Paid,
                    'paid_at' => $paidAt,
                ])->save();

                $entitlement = $access->grant($payment->setRelation('payable', $series));
                $entitlement->forceFill([
                    'starts_at' => $paidAt,
                    'ends_at' => $series->validity_days ? $paidAt->copy()->addDays($series->validity_days) : null,
                ])->save();
            }
        }
    }
}
