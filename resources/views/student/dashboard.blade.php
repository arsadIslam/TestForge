<x-student-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Welcome, :name', ['name' => Auth::user()->name]) }}</h2>
            <x-primary-link href="{{ route('student.tests.index') }}">Find a test</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Tests taken" :value="$summary['tests']" />
                <x-stat-card label="Average score" :value="($summary['avg_percent'] ?? '0').'%'" />
                <x-stat-card label="Best score" :value="($summary['best_percent'] ?? '0').'%'" />
                <x-stat-card label="Pass rate" :value="($summary['pass_rate'] ?? '0').'%'" />
            </div>

            @foreach ($inProgress as $attempt)
                <div class="flex items-center justify-between rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
                    <span>You have <strong>{{ $attempt->test->title }}</strong> in progress — the timer is still running.</span>
                    <a href="{{ route('student.attempts.show', $attempt) }}" class="font-semibold text-amber-800 underline">Resume</a>
                </div>
            @endforeach
            @foreach ($beingMarked as $attempt)
                <div class="flex items-center justify-between rounded-md border border-gray-200 bg-white px-4 py-3 text-sm">
                    <span><strong>{{ $attempt->test->title }}</strong> is being marked.</span>
                    <a href="{{ route('student.attempts.result', $attempt) }}" class="text-indigo-600 underline">View</a>
                </div>
            @endforeach

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b px-6 py-4"><h3 class="font-semibold text-gray-800">My results</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Test</th>
                                <th class="px-4 py-3 text-right">Score</th>
                                <th class="px-4 py-3 text-right">Accuracy</th>
                                <th class="px-4 py-3">Result</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($results as $result)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $result->test->title }} <span class="text-xs text-gray-400">#{{ $result->attempt->attempt_no }}</span></td>
                                    <td class="px-4 py-3 text-right">{{ $result->score }} / {{ $result->test->total_marks }}</td>
                                    <td class="px-4 py-3 text-right">{{ $result->accuracy }}%</td>
                                    <td class="px-4 py-3">
                                        <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-green-100 text-green-800' => $result->is_passed, 'bg-red-100 text-red-800' => ! $result->is_passed])>
                                            {{ $result->is_passed ? 'Passed' : 'Not passed' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $result->evaluated_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('student.attempts.result', $result->attempt_id) }}" class="text-indigo-600 hover:underline">Review</a>
                                        <a href="{{ route('student.tests.leaderboard', $result->test->slug) }}" class="ms-3 text-gray-600 hover:underline">Leaderboard</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No results yet — <a href="{{ route('student.tests.index') }}" class="text-indigo-600 underline">take your first test</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $results->links() }}
        </div>
    </div>
</x-student-layout>
