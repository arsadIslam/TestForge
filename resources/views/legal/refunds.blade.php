@extends('legal._page', ['title' => 'Refund & Cancellation Policy', 'updated' => '13 November 2026'])

@section('body')
    <p>We want you to be happy with what you buy on {{ config('app.name') }}.</p>
    <h2>Refunds</h2>
    <ul>
        <li>You can ask for a full refund within <strong>7 days</strong> of purchase if you have not started any test in the series you bought (or, for an All-Access pass, any paid test).</li>
        <li>If a technical fault on our side stopped you from taking a test you paid for, we'll refund you or extend your access — your choice.</li>
        <li>Approved refunds are sent back to the original payment method through Razorpay, usually within 5–7 working days.</li>
    </ul>
    <h2>Cancellation</h2>
    <p>Purchases are one-time payments — nothing renews automatically, so there is nothing to cancel. Your access simply ends on the date shown in <em>My purchases</em>.</p>
    <h2>How to ask</h2>
    <p>Use the <a href="{{ route('contact') }}" class="underline">contact form</a> with the email on your account and the date of purchase.</p>
@endsection
