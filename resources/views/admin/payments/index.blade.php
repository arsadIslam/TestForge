<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Payments') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Revenue" :value="\App\Support\Money::format((int) ($totals['paid']->amount_paise ?? 0))" :hint="($totals['paid']->payments ?? 0).' paid order(s)'" />
                <x-stat-card label="Open orders" :value="$totals['created']->payments ?? 0" hint="checkout started, not paid" />
                <x-stat-card label="Failed" :value="$totals['failed']->payments ?? 0" />
            </div>

            <form method="GET" class="flex flex-wrap items-end gap-3 bg-white p-4 shadow-sm sm:rounded-lg">
                <div>
                    <x-input-label for="search" value="Student" />
                    <x-text-input id="search" name="search" type="search" class="mt-1 block w-64" placeholder="Name or email" :value="$filters['search']" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <x-select-input id="status" name="status" class="mt-1 block">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <x-primary-button>Filter</x-primary-button>
            </form>

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Bought</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Gateway ids</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-4 py-3 text-gray-500">{{ $payment->id }}</td>
                                <td class="px-4 py-3">{{ $payment->user->name }}<div class="text-xs text-gray-500">{{ $payment->user->email }}</div></td>
                                <td class="px-4 py-3">{{ $payment->payable?->name ?? '—' }}<div class="text-xs text-gray-500">{{ $payment->payable_type === 'plan' ? 'All-Access pass' : 'Test series' }}</div></td>
                                <td class="px-4 py-3 text-right font-medium">{{ $payment->amount }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-green-100 text-green-800' => $payment->status === \App\Enums\PaymentStatus::Paid,
                                        'bg-amber-100 text-amber-800' => $payment->status === \App\Enums\PaymentStatus::Created,
                                        'bg-red-100 text-red-800' => $payment->status === \App\Enums\PaymentStatus::Failed,
                                    ])>{{ $payment->status->label() }}</span>
                                    @if ($payment->failure_reason)<div class="mt-1 text-xs text-red-700">{{ $payment->failure_reason }}</div>@endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $payment->gateway_order_id ?? '—' }}<br>{{ $payment->gateway_payment_id }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No payments match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        </div>
    </div>
</x-admin-layout>
