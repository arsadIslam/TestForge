<x-teacher-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My tests') }}</h2>
            <x-primary-link href="{{ route('teacher.tests.create') }}">New test</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Test</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Questions</th>
                            <th class="px-4 py-3 text-right">Marks (pass)</th>
                            <th class="px-4 py-3 text-right">Duration</th>
                            <th class="px-4 py-3">Window</th>
                            <th class="px-4 py-3 text-right">Attempts</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($tests as $test)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('teacher.tests.show', $test) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $test->title }}</a>
                                    <div class="mt-0.5 text-xs {{ $test->series ? 'text-amber-700' : 'text-gray-400' }}">{{ $test->series?->name ?? 'Free' }}</div>
                                </td>
                                <td class="px-4 py-3"><x-test-status-badge :status="$test->status" /></td>
                                <td class="px-4 py-3 text-right">{{ $test->questions_count }}</td>
                                <td class="px-4 py-3 text-right">{{ $test->total_marks }} <span class="text-gray-400">({{ $test->pass_marks }})</span></td>
                                <td class="px-4 py-3 text-right">{{ $test->duration_minutes }} min</td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $test->starts_at?->format('d M Y H:i') ?? 'Any time' }}<br>
                                    → {{ $test->ends_at?->format('d M Y H:i') ?? 'no end' }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ $test->attempts_count }}</td>
                                <td class="px-4 py-3">@include('teacher.tests._actions')</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">You haven't created any tests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tests->links() }}
        </div>
    </div>
</x-teacher-layout>
