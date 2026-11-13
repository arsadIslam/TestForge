<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Services\Payments\RazorpayGateway;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/webhooks/razorpay — Razorpay → our server, even if the student closed the tab.
 *
 * Verified with HMAC-SHA256 of the RAW body (any change to the body breaks the signature).
 * Processing is idempotent (PaymentService locks the payment row), so Razorpay's retries and
 * the browser callback can arrive in any order without granting access twice.
 */
class RazorpayWebhookController extends Controller
{
    public function __invoke(Request $request, RazorpayGateway $gateway, PaymentService $payments): JsonResponse
    {
        if (! $gateway->verifyWebhookSignature($request->getContent(), (string) $request->header('X-Razorpay-Signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $event = $request->json('event');
        $payment = $request->json('payload.payment.entity', []);
        $orderId = $payment['order_id'] ?? $request->json('payload.order.entity.id');

        try {
            match ($event) {
                'payment.captured', 'order.paid' => $orderId && isset($payment['id'])
                    ? $payments->markPaid($orderId, $payment['id'], isset($payment['amount']) ? (int) $payment['amount'] : null)
                    : null,
                'payment.failed' => $orderId
                    ? $payments->markFailed($orderId, $payment['error_description'] ?? 'Payment failed')
                    : null,
                default => null, // other events are not used
            };
        } catch (BusinessRuleException $e) {
            // e.g. an order that isn't ours: log it, but answer 200 so Razorpay doesn't retry forever.
            Log::warning('Razorpay webhook ignored', ['event' => $event, 'order_id' => $orderId, 'reason' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }
}
