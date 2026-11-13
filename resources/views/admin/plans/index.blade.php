<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('All-Access passes') }}</h2>
            <x-primary-link href="{{ route('admin.plans.create') }}">New pass</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm text-gray-600">A one-time payment that unlocks every test for a number of days. Buying again while a pass is active extends it.</p>
            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Pass</th>
                            <th class="px-4 py-3 text-right">Price</th>
                            <th class="px-4 py-3 text-right">Days</th>
                            <th class="px-4 py-3 text-right">Sales</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($plans as $plan)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $plan->name }}</td>
                                <td class="px-4 py-3 text-right">{{ $plan->price }}</td>
                                <td class="px-4 py-3 text-right">{{ $plan->duration_days }}</td>
                                <td class="px-4 py-3 text-right">{{ $plan->sales_count }}</td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\Money::format((int) $plan->revenue_paise) }}</td>
                                <td class="px-4 py-3">
                                    <span @class(['rounded-full px-2 py-0.5 text-xs font-medium', 'bg-green-100 text-green-700' => $plan->is_active, 'bg-gray-200 text-gray-600' => ! $plan->is_active])>{{ $plan->is_active ? 'On sale' : 'Inactive' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.plans.edit', $plan) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('Delete this pass?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No passes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $plans->links() }}
        </div>
    </div>
</x-admin-layout>
