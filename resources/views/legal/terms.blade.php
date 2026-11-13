@extends('legal._page', ['title' => 'Terms of Service', 'updated' => '13 November 2026'])

@section('body')
    <p>These terms apply to your use of {{ config('app.name') }} ("we", "us"). By creating an account you agree to them.</p>
    <h2>1. Accounts</h2>
    <ul>
        <li>You must give accurate details and keep your password secret. You are responsible for activity on your account.</li>
        <li>One person per account. We may suspend accounts that are shared, abused or used to cheat.</li>
    </ul>
    <h2>2. Tests and results</h2>
    <ul>
        <li>Tests are timed by our server. Answers submitted after the deadline are not accepted.</li>
        <li>Results and rankings are for practice only and do not guarantee any exam outcome.</li>
        <li>You must not copy, publish or resell our questions or tests.</li>
    </ul>
    <h2>3. Paid series and passes</h2>
    <ul>
        <li>Prices are shown in Indian rupees (₹) before payment. Payments are processed by Razorpay; we never see or store your card details.</li>
        <li>A purchase gives you access for the period shown (or for the lifetime of the service where stated). Access is personal and can't be transferred.</li>
        <li>Refunds are covered by our <a href="{{ route('legal.refunds') }}" class="underline">Refund &amp; Cancellation policy</a>.</li>
    </ul>
    <h2>4. Availability and changes</h2>
    <p>We aim to keep the service available but can't promise it will be uninterrupted. We may change these terms; we'll update the date above and, for significant changes, tell you by email.</p>
    <h2>5. Contact</h2>
    <p>Questions about these terms: <a href="{{ route('contact') }}" class="underline">contact us</a> or write to {{ config('mail.from.address') }}.</p>
@endsection
