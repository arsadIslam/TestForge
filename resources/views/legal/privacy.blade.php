@extends('legal._page', ['title' => 'Privacy Policy', 'updated' => '13 November 2026'])

@section('body')
    <p>This policy explains what personal data {{ config('app.name') }} collects and how it is used.</p>
    <h2>What we collect</h2>
    <ul>
        <li><strong>Account details:</strong> your name, email address and a securely hashed password.</li>
        <li><strong>Test activity:</strong> your answers, scores, times and the IP address used when a test was started.</li>
        <li><strong>Payments:</strong> what you bought, the amount, the date and Razorpay's order/payment references. Card and bank details go directly to Razorpay and never reach our servers.</li>
        <li><strong>Messages</strong> you send through the contact form.</li>
    </ul>
    <h2>How we use it</h2>
    <ul>
        <li>To run your account, score your tests, show leaderboards (your name and best score) and your analytics.</li>
        <li>To process purchases and give you the access you paid for.</li>
        <li>To answer your messages and keep the service secure.</li>
    </ul>
    <p>We do not sell your data. We share it only with the services needed to run {{ config('app.name') }} (hosting, email delivery, Razorpay for payments).</p>
    <h2>Cookies</h2>
    <p>We use essential cookies only: to keep you signed in and to protect forms against forgery.</p>
    <h2>Your rights</h2>
    <p>You can update your details or delete your account from your profile page at any time. To ask for a copy of your data, <a href="{{ route('contact') }}" class="underline">contact us</a>.</p>
@endsection
