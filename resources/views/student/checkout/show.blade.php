<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Checkout') }}</h2>
    </x-slot>

    {{-- Razorpay's own script: shows the payment popup (cards, UPI, netbanking; test mode with test keys). --}}
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <div class="py-12" x-data="razorpayCheckout(@js($options))">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="text-sm text-gray-500">You are buying</div>
                <div class="mt-1 text-lg font-semibold text-gray-900">{{ $payment->payable->name }}</div>
                <div class="mt-4 flex items-baseline justify-between border-t pt-4">
                    <span class="text-gray-600">Total</span>
                    <span class="text-2xl font-bold text-gray-900">{{ $payment->amount }}</span>
                </div>

                <p x-show="message" x-text="message" class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800"></p>

                <div class="mt-6 flex items-center gap-4">
                    <x-primary-button type="button" @click="open()" x-bind:disabled="busy">Pay {{ $payment->amount }}</x-primary-button>
                    <a href="{{ route('student.series.index') }}" class="text-sm text-gray-600 underline">Cancel</a>
                </div>
                <p class="mt-4 text-xs text-gray-500">Payments are processed securely by Razorpay. TestForge never sees your card details.</p>
            </div>
        </div>

        {{-- Filled by Checkout.js on success, then submitted; the server re-checks the signature. --}}
        <form x-ref="verifyForm" method="POST" action="{{ route('student.checkout.verify') }}" class="hidden">
            @csrf
            <input type="hidden" name="razorpay_order_id">
            <input type="hidden" name="razorpay_payment_id">
            <input type="hidden" name="razorpay_signature">
        </form>
    </div>
</x-student-layout>
