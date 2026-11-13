<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The only class that talks to Razorpay (Standard Checkout, test or live keys).
 * Uses Laravel's HTTP client instead of the SDK, so tests can fake it with Http::fake().
 *
 * Keys come from config/services.php (RAZORPAY_KEY_ID / _KEY_SECRET / _WEBHOOK_SECRET).
 */
class RazorpayGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    /**
     * Public key for Checkout.js (safe to put in the page; the secret never leaves the server).
     */
    public function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    /**
     * POST /v1/orders — the amount is fixed on Razorpay's side here, so the browser can't change it.
     *
     * @param  array<string, string>  $notes
     * @return array{id: string, amount: int, currency: string, status: string}
     */
    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        $response = Http::withBasicAuth(config('services.razorpay.key_id'), config('services.razorpay.key_secret'))
            ->acceptJson()
            ->timeout(15)
            ->post(rtrim(config('services.razorpay.base_url'), '/').'/orders', [
                'amount' => $amountPaise,
                'currency' => 'INR',
                'receipt' => $receipt,
                'notes' => $notes,
            ]);

        if ($response->failed() || ! is_string($response->json('id'))) {
            throw new RuntimeException('Razorpay order creation failed: HTTP '.$response->status().' '.$response->json('error.description', ''));
        }

        return $response->json();
    }

    /**
     * Checkout.js returns razorpay_signature = HMAC-SHA256(order_id + "|" + payment_id, key_secret).
     * Only someone with our secret could produce it, so a valid signature proves the payment is real.
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, (string) config('services.razorpay.key_secret'));

        return hash_equals($expected, $signature); // constant-time: no timing attack
    }

    /**
     * Webhooks are signed with the separate webhook secret over the RAW request body.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');

        return $secret !== '' && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }
}
