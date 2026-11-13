<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My purchases') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b px-6 py-4"><h3 class="font-semibold text-gray-800">Your access</h3></div>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($entitlements as $entitlement)
                        <li class="flex items-center justify-between px-6 py-3">
                            <span class="font-medium text-gray-900">{{ $entitlement->series?->name ?? 'All Access (every test)' }}</span>
                            <span class="text-gray-600">
                                @if ($entitlement->starts_at->isFuture()) starts {{ $entitlement->starts_at->format('d M Y') }} · @endif
                                {{ $entitlement->ends_at ? 'until '.$entitlement->ends_at->format('d M Y') : 'lifetime' }}
                            </span>
                        </li>
                    @empty
                        <li class="px-6 py-6 text-center text-gray-500">Nothing yet. <a href="{{ route('student.series.index') }}" class="text-indigo-600 underline">Browse test series</a>.</li>
                    @endforelse
                </ul>
            </section>

            <section class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr><th class="px-4 py-3">Item</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-4 py-3">{{ $payment->payable?->name }}</td>
                                <td class="px-4 py-3 text-right">{{ $payment->amount }}</td>
                                <td class="px-4 py-3">{{ $payment->status->label() }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $payments->links() }}
        </div>
    </div>
</x-student-layout>
