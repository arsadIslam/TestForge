<?php

namespace App\Http\Controllers\Web\Student;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CheckoutRequest;
use App\Http\Requests\Student\VerifyPaymentRequest;
use App\Models\Payment;
use App\Models\TestSeries;
use App\Services\Payments\RazorpayGateway;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Razorpay Standard Checkout:
 *   POST /student/checkout        → create our payment + a Razorpay order
 *   GET  /student/checkout/{id}   → page that opens Razorpay's popup
 *   POST /student/checkout/verify → popup succeeded: check the signature, mark paid, grant access
 * The webhook (Api\RazorpayWebhookController) does the same marking if the tab was closed.
 */
class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, PaymentService $payments): RedirectResponse
    {
        $payment = $payments->createOrder($request->user(), $request->item());

        return redirect()->route('student.checkout.show', $payment);
    }

    public function show(Request $request, Payment $payment, RazorpayGateway $gateway): View|RedirectResponse
    {
        $payment->load('payable');

        if ($payment->status === PaymentStatus::Paid) {
            return $this->afterPayment($payment)->with('success', 'This order is already paid.');
        }

        abort_if($payment->status === PaymentStatus::Failed || $payment->gateway_order_id === null, 404);

        return view('student.checkout.show', [
            'payment' => $payment,
            // Everything Checkout.js needs. key_id is public; the secret never leaves the server.
            'options' => [
                'key' => $gateway->keyId(),
                'amount' => $payment->amount_paise,
                'currency' => $payment->currency,
                'order_id' => $payment->gateway_order_id,
                'name' => config('app.name'),
                'description' => $payment->payable->name,
                'prefill' => ['name' => $request->user()->name, 'email' => $request->user()->email],
                'notes' => ['payment_id' => (string) $payment->id],
                'theme' => ['color' => '#4f46e5'],
            ],
        ]);
    }

    public function verify(VerifyPaymentRequest $request, RazorpayGateway $gateway, PaymentService $payments): RedirectResponse
    {
        $orderId = $request->validated('razorpay_order_id');

        // The order must be one of THIS student's checkouts.
        $payment = Payment::where('gateway_order_id', $orderId)->where('user_id', $request->user()->id)->firstOrFail();

        if (! $gateway->verifyPaymentSignature($orderId, $request->validated('razorpay_payment_id'), $request->validated('razorpay_signature'))) {
            return redirect()->route('student.checkout.show', $payment)
                ->with('error', 'We could not verify this payment. If money was taken it will be confirmed automatically within a few minutes.');
        }

        $payment = $payments->markPaid($orderId, $request->validated('razorpay_payment_id'));

        return $this->afterPayment($payment->load('payable'))->with('success', "Payment received — {$payment->payable->name} is unlocked.");
    }

    private function afterPayment(Payment $payment): RedirectResponse
    {
        return $payment->payable instanceof TestSeries
            ? redirect()->route('student.series.show', $payment->payable)
            : redirect()->route('student.series.index');
    }
}
