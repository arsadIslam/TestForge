<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\TestSeries;
use App\Models\User;
use App\Services\Payments\RazorpayGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Checkout: create an order → the student pays in Razorpay's popup → mark it paid once.
 *
 * markPaid() can be reached twice for the same payment — from the browser callback AND from
 * the webhook — so it locks the payment row and does nothing if it is already paid. Together
 * with unique(entitlements.payment_id), access is granted exactly once.
 */
class PaymentService
{
    public function __construct(
        private RazorpayGateway $gateway,
        private AccessService $access,
    ) {}

    public function createOrder(User $student, TestSeries|Plan $item): Payment
    {
        if (! $item->is_active) {
            throw new BusinessRuleException('This item is no longer for sale.');
        }

        if (! $this->gateway->isConfigured()) {
            throw new BusinessRuleException('Online payments are not set up yet. Please try again later.');
        }

        // 1. Our own record first, with the price FROM THE DATABASE (never from the request).
        $payment = new Payment;
        $payment->forceFill([
            'user_id' => $student->id,
            'payable_type' => $item->getMorphClass(),
            'payable_id' => $item->id,
            'amount_paise' => $item->price_paise,
            'currency' => 'INR',
            'gateway' => 'razorpay',
            'status' => PaymentStatus::Created,
        ])->save();

        // 2. Then the network call — deliberately NOT inside a DB transaction, so a slow
        //    gateway never holds database locks.
        try {
            $order = $this->gateway->createOrder($payment->amount_paise, 'payment_'.$payment->id, [
                'payment_id' => (string) $payment->id,
                'user_id' => (string) $student->id,
            ]);
        } catch (Throwable $e) {
            $payment->forceFill(['status' => PaymentStatus::Failed, 'failure_reason' => 'Order could not be created'])->save();
            Log::warning('Razorpay order failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            throw new BusinessRuleException('We could not start the payment. Please try again in a moment.');
        }

        $payment->forceFill(['gateway_order_id' => $order['id']])->save();

        return $payment;
    }

    /**
     * Idempotent: safe to call again for the same order (browser callback + webhook, retries).
     *
     * @param  int|null  $amountPaise  amount reported by the gateway (webhook) — must match ours
     */
    public function markPaid(string $orderId, string $paymentId, ?int $amountPaise = null): Payment
    {
        return DB::transaction(function () use ($orderId, $paymentId, $amountPaise): Payment {
            $payment = Payment::query()->where('gateway_order_id', $orderId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Unknown payment order.');

            if ($payment->status === PaymentStatus::Paid) {
                return $payment; // already handled (the other path got here first)
            }

            if ($amountPaise !== null && $amountPaise !== $payment->amount_paise) {
                Log::error('Razorpay amount mismatch', ['payment_id' => $payment->id, 'expected' => $payment->amount_paise, 'got' => $amountPaise]);

                throw new BusinessRuleException('Payment amount does not match the order.');
            }

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'gateway_payment_id' => $paymentId,
                'paid_at' => now(),
                'failure_reason' => null,
            ])->save();

            $this->access->grant($payment);

            return $payment;
        });
    }

    /**
     * Only an order that is still open can fail; a paid one stays paid.
     */
    public function markFailed(string $orderId, string $reason): void
    {
        Payment::query()
            ->where('gateway_order_id', $orderId)
            ->where('status', PaymentStatus::Created)
            ->update(['status' => PaymentStatus::Failed->value, 'failure_reason' => mb_substr($reason, 0, 255)]);
    }
}
