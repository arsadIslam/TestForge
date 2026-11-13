<?php

use App\Enums\PaymentStatus;
use App\Models\Entitlement;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\TestSeries;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
| Razorpay checkout (Phase F). The Razorpay API is faked with Http::fake(); signatures are
| computed with the test secrets exactly as Razorpay computes them.
*/

beforeEach(function () {
    config([
        'services.razorpay.key_id' => 'rzp_test_KEY',
        'services.razorpay.key_secret' => 'test_secret',
        'services.razorpay.webhook_secret' => 'webhook_secret',
    ]);
    // Razorpay answers from this queue; a test can replace it (e.g. with an error response).
    $this->razorpay = collect([
        Http::response(['id' => 'order_AAA', 'amount' => 29900, 'currency' => 'INR', 'status' => 'created']),
        Http::response(['id' => 'order_BBB', 'amount' => 29900, 'currency' => 'INR', 'status' => 'created']),
    ]);
    Http::fake(['api.razorpay.com/*' => fn () => $this->razorpay->shift()]);

    $this->series = TestSeries::factory()->create(['name' => 'SSC', 'slug' => 'ssc', 'price_paise' => 29900]);
    $this->student = User::factory()->student()->create();
});

function checkout(object $t, string $type = 'series', ?int $id = null, array $extra = [])
{
    return $t->actingAs($t->student)->post('/student/checkout', ['type' => $type, 'id' => $id ?? $t->series->id, ...$extra]);
}

function signature(string $orderId, string $paymentId): string
{
    return hash_hmac('sha256', "{$orderId}|{$paymentId}", 'test_secret');
}

function verifyCheckout(object $t, string $orderId, string $paymentId, ?string $signature = null)
{
    return $t->actingAs($t->student)->post('/student/checkout/verify', [
        'razorpay_order_id' => $orderId, 'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature ?? signature($orderId, $paymentId),
    ]);
}

function webhook(object $t, array $body, ?string $signature = null)
{
    $raw = json_encode($body);

    return $t->call('POST', '/api/webhooks/razorpay', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_RAZORPAY_SIGNATURE' => $signature ?? hash_hmac('sha256', $raw, 'webhook_secret'),
    ], $raw);
}

function capturedEvent(string $orderId, string $paymentId, int $amount = 29900): array
{
    return ['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => [
        'id' => $paymentId, 'order_id' => $orderId, 'amount' => $amount, 'status' => 'captured',
    ]]]];
}

it('creates a Razorpay order for the price in the database, not from the request', function () {
    checkout($this, extra: ['amount' => 100, 'amount_paise' => 100])->assertRedirect();

    $payment = Payment::sole();
    expect($payment->amount_paise)->toBe(29900)
        ->and($payment->status)->toBe(PaymentStatus::Created)
        ->and($payment->gateway_order_id)->toBe('order_AAA');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.razorpay.com/v1/orders'
        && $request['amount'] === 29900 && $request['currency'] === 'INR'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_test_KEY:test_secret')));

    $this->actingAs($this->student)->get("/student/checkout/{$payment->id}")
        ->assertOk()->assertSee('checkout.razorpay.com/v1/checkout.js', false)
        ->assertSee('order_AAA')->assertSee('rzp_test_KEY')->assertDontSee('test_secret');
});

it('grants access after a correctly signed payment — exactly once', function () {
    checkout($this);

    verifyCheckout($this, 'order_AAA', 'pay_111')->assertRedirect('/student/series/ssc')->assertSessionHas('success');
    verifyCheckout($this, 'order_AAA', 'pay_111'); // browser resubmits

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->gateway_payment_id)->toBe('pay_111')
        ->and(Entitlement::count())->toBe(1)
        ->and(Entitlement::sole()->test_series_id)->toBe($this->series->id);
});

it('rejects a forged signature and grants nothing', function () {
    checkout($this);

    verifyCheckout($this, 'order_AAA', 'pay_111', 'forged')->assertSessionHas('error');

    expect(Payment::sole()->status)->toBe(PaymentStatus::Created)->and(Entitlement::count())->toBe(0);
});

it('does not let a student verify someone else\'s order', function () {
    checkout($this);
    $this->student = User::factory()->student()->create();

    verifyCheckout($this, 'order_AAA', 'pay_111')->assertNotFound();
    $this->actingAs($this->student)->get('/student/checkout/'.Payment::sole()->id)->assertForbidden();
});

it('confirms the payment from the webhook if the tab was closed', function () {
    checkout($this);

    webhook($this, capturedEvent('order_AAA', 'pay_222'))->assertOk()->assertJson(['status' => 'ok']);

    expect(Payment::sole()->status)->toBe(PaymentStatus::Paid)->and(Entitlement::count())->toBe(1);
});

it('grants once when both the browser and the webhook confirm', function () {
    checkout($this);

    webhook($this, capturedEvent('order_AAA', 'pay_333'));
    webhook($this, capturedEvent('order_AAA', 'pay_333')); // Razorpay retry
    verifyCheckout($this, 'order_AAA', 'pay_333');

    expect(Entitlement::count())->toBe(1);
});

it('rejects a webhook with a bad signature', function () {
    checkout($this);

    webhook($this, capturedEvent('order_AAA', 'pay_444'), 'bad')->assertStatus(400);

    expect(Payment::sole()->status)->toBe(PaymentStatus::Created);
});

it('ignores a webhook whose amount does not match the order', function () {
    checkout($this);

    webhook($this, capturedEvent('order_AAA', 'pay_555', amount: 100))->assertOk();

    expect(Payment::sole()->status)->toBe(PaymentStatus::Created)->and(Entitlement::count())->toBe(0);
});

it('marks a payment failed from the webhook, but never un-pays a paid one', function () {
    checkout($this);
    $failed = ['event' => 'payment.failed', 'payload' => ['payment' => ['entity' => ['id' => 'pay_6', 'order_id' => 'order_AAA', 'error_description' => 'Card declined']]]];

    webhook($this, $failed)->assertOk();
    expect(Payment::sole()->status)->toBe(PaymentStatus::Failed)->and(Payment::sole()->failure_reason)->toBe('Card declined');

    checkout($this); // second order: paid, then a late "failed" arrives
    webhook($this, capturedEvent('order_BBB', 'pay_7'));
    webhook($this, [...$failed, 'payload' => ['payment' => ['entity' => ['id' => 'pay_8', 'order_id' => 'order_BBB']]]]);
    expect(Payment::where('gateway_order_id', 'order_BBB')->value('status'))->toBe(PaymentStatus::Paid);
});

it('handles a Razorpay outage gracefully', function () {
    $this->razorpay = collect([Http::response(['error' => ['description' => 'down']], 500)]);

    checkout($this)->assertSessionHas('error', 'We could not start the payment. Please try again in a moment.');

    expect(Payment::sole()->status)->toBe(PaymentStatus::Failed);
});

it('charges the price at the time of ordering, even if it changes later', function () {
    checkout($this);
    $this->series->update(['price_paise' => 99900]);

    webhook($this, capturedEvent('order_AAA', 'pay_9', amount: 29900))->assertOk();

    expect(Payment::sole()->status)->toBe(PaymentStatus::Paid)->and(Payment::sole()->amount_paise)->toBe(29900);
});

it('sells All-Access passes the same way', function () {
    $plan = Plan::factory()->create(['price_paise' => 19900, 'duration_days' => 30]);
    $this->razorpay = collect([Http::response(['id' => 'order_PLAN', 'amount' => 19900, 'currency' => 'INR', 'status' => 'created'])]);

    checkout($this, 'plan', $plan->id);
    verifyCheckout($this, 'order_PLAN', 'pay_10')->assertRedirect('/student/series');

    expect(Entitlement::sole()->test_series_id)->toBeNull()
        ->and(Payment::sole()->payable_type)->toBe('plan'); // morph map: a short name, not a class name
});

it('refuses checkout when payments are not configured, or the item is not on sale', function () {
    config(['services.razorpay.key_id' => null]);
    checkout($this)->assertSessionHas('error', 'Online payments are not set up yet. Please try again later.');

    config(['services.razorpay.key_id' => 'rzp_test_KEY']);
    $this->series->update(['is_active' => false]);
    checkout($this)->assertSessionHas('error', 'This item is no longer for sale.');

    expect(Payment::count())->toBe(0);
});

it('does not rate-limit the webhook like the public API', function () {
    checkout($this);

    foreach (range(1, 65) as $i) {
        webhook($this, capturedEvent('order_AAA', 'pay_x'))->assertOk();
    }
});
