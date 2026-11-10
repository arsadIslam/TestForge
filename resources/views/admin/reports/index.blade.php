<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Reports') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (count($untouched))
                <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <strong>{{ count($untouched) }} published {{ Str::plural('test', count($untouched)) }} with no attempts yet:</strong>
                    @foreach ($untouched as $test)
                        {{ $test['title'] }} ({{ $test['teacher'] }}){{ $loop->last ? '' : ',' }}
                    @endforeach
                </div>
            @endif

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Test</th>
                            <th class="px-4 py-3">Teacher</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Questions</th>
                            <th class="px-4 py-3 text-right">Results</th>
                            <th class="px-4 py-3 text-right">Avg score</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($tests as $test)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $test->title }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $test->creator->name }}</td>
                                <td class="px-4 py-3"><x-test-status-badge :status="$test->status" /></td>
                                <td class="px-4 py-3 text-right">{{ $test->questions_count }}</td>
                                <td class="px-4 py-3 text-right">{{ $test->results_count }}</td>
                                <td class="px-4 py-3 text-right">{{ $test->results_avg_score !== null ? number_format((float) $test->results_avg_score, 2).' / '.$test->total_marks : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.reports.show', $test) }}" class="text-indigo-600 hover:underline">Analytics</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No tests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tests->links() }}
        </div>
    </div>
</x-admin-layout>
