<?php

use App\Enums\PaymentStatus;
use App\Models\Entitlement;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Test;
use App\Models\TestSeries;
use App\Models\User;
use App\Services\AccessService;
use App\Services\AttemptService;
use Illuminate\Support\Facades\Queue;

/*
| Paid series and All-Access passes (Phase E) — no gateway involved here.
*/

beforeEach(function () {
    Queue::fake();
    $this->series = TestSeries::factory()->create(['name' => 'SSC', 'slug' => 'ssc', 'validity_days' => 30]);
    $this->paid = publishedTest(attributes: ['test_series_id' => $this->series->id]);
    $this->free = publishedTest();
    $this->student = User::factory()->student()->create();
});

/**
 * A paid payment for $item, turned into access the same way checkout does.
 */
function buy(User $student, TestSeries|Plan $item): Entitlement
{
    $payment = new Payment;
    $payment->forceFill([
        'user_id' => $student->id, 'payable_type' => $item->getMorphClass(), 'payable_id' => $item->id,
        'amount_paise' => $item->price_paise, 'gateway' => 'test', 'status' => PaymentStatus::Paid, 'paid_at' => now(),
    ])->save();

    return app(AccessService::class)->grant($payment);
}

function startAttempt(object $t, $test)
{
    return $t->actingAs($t->student)->post("/student/tests/{$test->slug}/attempts");
}

it('lets anyone start a free test', function () {
    startAttempt($this, $this->free)->assertRedirect();
    expect($this->student->attempts()->count())->toBe(1);
});

it('refuses a paid test until the series is bought', function () {
    startAttempt($this, $this->paid)->assertSessionHas('error', fn ($m) => str_contains($m, 'part of the SSC series'));
    expect($this->student->attempts()->count())->toBe(0);

    buy($this->student, $this->series);

    startAttempt($this, $this->paid)->assertRedirect();
    expect($this->student->attempts()->count())->toBe(1);
});

it('unlocks every series with an All-Access pass', function () {
    buy($this->student, Plan::factory()->create(['duration_days' => 30]));

    startAttempt($this, $this->paid)->assertRedirect();
    expect($this->student->attempts()->count())->toBe(1);
});

it('locks the series again when access expires, but lets an attempt in progress finish', function () {
    buy($this->student, $this->series); // 30 days

    // Start 2 minutes before access ends (the test lasts 10 minutes)…
    $this->travelTo(now()->addDays(30)->subMinutes(2));
    $attempt = app(AttemptService::class)->start($this->student, $this->paid);

    // …then access ends while the student is still answering.
    $this->travel(3)->minutes();

    expect(app(AccessService::class)->canStart($this->student, $this->paid))->toBeFalse()
        ->and(app(AttemptService::class)->start($this->student, $this->paid)->id)->toBe($attempt->id); // resume is allowed

    // once that attempt is over, a NEW one is refused
    $this->travel(10)->minutes();
    startAttempt($this, $this->paid)->assertSessionHas('error', fn ($m) => str_contains($m, 'Buy the series'));
});

it('stacks a second purchase after the current one', function () {
    $first = buy($this->student, $this->series);
    $second = buy($this->student, $this->series);

    expect($second->starts_at->equalTo($first->ends_at))->toBeTrue()
        ->and($second->ends_at->equalTo($first->ends_at->copy()->addDays(30)))->toBeTrue();
});

it('gives lifetime access when the series has no validity', function () {
    $lifetime = TestSeries::factory()->lifetime()->create();
    expect(buy($this->student, $lifetime)->ends_at)->toBeNull();

    $this->travel(5)->years();
    expect(app(AccessService::class)->accessFor($this->student)->ownsSeries($lifetime->id))->toBeTrue();
});

it('shows locked tests with an Unlock button, and access in one query', function () {
    $page = $this->actingAs($this->student)->get('/student/tests')->assertOk();
    $page->assertSee('🔒 Unlock', false)->assertSee('Free');

    buy($this->student, $this->series);
    $this->actingAs($this->student)->get('/student/tests')->assertDontSee('🔒 Unlock', false);
});

it('marks locked tests in the API', function () {
    $headers = ['Authorization' => 'Bearer '.$this->student->createToken('t')->plainTextToken];

    $locked = collect($this->get('/api/v1/tests', $headers)->assertOk()->json('data'))->keyBy('id');
    expect($locked[$this->paid->id]['is_locked'])->toBeTrue()
        ->and($locked[$this->paid->id]['series']['name'])->toBe('SSC')
        ->and($locked[$this->free->id]['is_locked'])->toBeFalse()
        ->and($locked[$this->free->id]['series'])->toBeNull();

    buy($this->student, $this->series);
    expect(collect($this->get('/api/v1/tests', $headers)->json('data'))->keyBy('id')[$this->paid->id]['is_locked'])->toBeFalse();
});

it('lets a teacher put a test in an active series only', function () {
    $teacher = User::factory()->teacher()->create();
    $inactive = TestSeries::factory()->inactive()->create();
    $payload = ['title' => 'Paid Mock', 'duration_minutes' => 20, 'pass_marks' => 0, 'max_attempts' => 1, 'shuffle_questions' => '0'];

    $this->actingAs($teacher)->post('/teacher/tests', [...$payload, 'test_series_id' => $inactive->id])->assertSessionHasErrors('test_series_id');
    $this->actingAs($teacher)->post('/teacher/tests', [...$payload, 'test_series_id' => $this->series->id])->assertSessionHasNoErrors();
    $this->actingAs($teacher)->post('/teacher/tests', [...$payload, 'title' => 'Free Mock', 'test_series_id' => ''])->assertSessionHasNoErrors();

    expect(Test::where('title', 'Paid Mock')->value('test_series_id'))->toBe($this->series->id)
        ->and(Test::where('title', 'Free Mock')->value('test_series_id'))->toBeNull();
});

it('lets the admin manage series and passes, with prices in paise', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/series', ['name' => 'Bank PO', 'price' => '149.50', 'validity_days' => '', 'is_active' => '1'])
        ->assertRedirect('/admin/series');
    $po = TestSeries::where('slug', 'bank-po')->first();
    expect($po->price_paise)->toBe(14950)->and($po->validity_days)->toBeNull()->and($po->price)->toBe('₹149.50');

    $this->actingAs($admin)->post('/admin/plans', ['name' => 'All Access · 7 days', 'price' => '49', 'duration_days' => 7, 'is_active' => '1'])
        ->assertRedirect('/admin/plans');
    expect(Plan::where('duration_days', 7)->value('price_paise'))->toBe(4900);

    // a bought series can't be deleted, only deactivated
    buy($this->student, $this->series);
    $this->actingAs($admin)->delete("/admin/series/{$this->series->id}")->assertSessionHas('error', fn ($m) => str_contains($m, 'Deactivate it instead'));
    $this->actingAs($admin)->delete("/admin/series/{$po->id}")->assertSessionHas('success');

    $this->actingAs($admin)->get('/admin/series')->assertOk()->assertSee('SSC');
    $this->actingAs($admin)->get('/admin/payments')->assertOk()->assertSee('₹299');
    $this->actingAs($this->student)->get('/admin/series')->assertForbidden();
});
