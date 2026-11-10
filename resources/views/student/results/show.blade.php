<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $attempt->test->title }}</h2>
                <p class="text-sm text-gray-500">Attempt {{ $attempt->attempt_no }} · submitted {{ $attempt->submitted_at->format('d M Y, H:i') }}
                    @if ($attempt->status === \App\Enums\AttemptStatus::Evaluated && $attempt->submitted_at->equalTo($attempt->expires_at)) (time ran out) @endif
                </p>
            </div>
            <a href="{{ route('student.tests.leaderboard', $attempt->test) }}" class="text-sm text-indigo-600 hover:underline">Leaderboard →</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Score summary --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Score</div>
                        <div class="text-4xl font-bold text-gray-900">{{ $result->score }} <span class="text-xl font-medium text-gray-400">/ {{ $attempt->test->total_marks }}</span></div>
                    </div>
                    <span @class([
                        'rounded-full px-4 py-1 text-sm font-bold uppercase tracking-wide',
                        'bg-green-100 text-green-800' => $result->is_passed,
                        'bg-red-100 text-red-800' => ! $result->is_passed,
                    ])>{{ $result->is_passed ? 'Passed' : 'Not passed' }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Pass mark: {{ $attempt->test->pass_marks }}</p>

                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-5 text-center">
                    <div class="rounded-md bg-green-50 p-3"><div class="text-2xl font-semibold text-green-700">{{ $result->correct_count }}</div><div class="text-xs text-gray-600">Correct</div></div>
                    <div class="rounded-md bg-red-50 p-3"><div class="text-2xl font-semibold text-red-700">{{ $result->wrong_count }}</div><div class="text-xs text-gray-600">Wrong</div></div>
                    <div class="rounded-md bg-gray-50 p-3"><div class="text-2xl font-semibold text-gray-700">{{ $result->skipped_count }}</div><div class="text-xs text-gray-600">Skipped</div></div>
                    <div class="rounded-md bg-gray-50 p-3"><div class="text-2xl font-semibold text-gray-700">{{ $result->accuracy }}%</div><div class="text-xs text-gray-600">Accuracy</div></div>
                    <div class="rounded-md bg-gray-50 p-3"><div class="text-2xl font-semibold text-gray-700"><x-duration :seconds="$result->time_taken_seconds" /></div><div class="text-xs text-gray-600">Time taken</div></div>
                </div>

                @if ($myRank)
                    <p class="mt-4 text-sm text-gray-700">
                        Your best attempt ranks <strong>#{{ $myRank['rank_no'] }}</strong> of {{ $myRank['total_students'] }} students
                        — better than {{ number_format((float) $myRank['percentile'], 1) }}% of them.
                    </p>
                @endif
            </div>

            {{-- Question-by-question review --}}
            <div class="space-y-4">
                @foreach ($review as $item)
                    @php($question = $item['question'])
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-start justify-between gap-4">
                            <p class="font-medium text-gray-900 whitespace-pre-line"><span class="text-gray-400">{{ $loop->iteration }}.</span> {{ $question->body }}</p>
                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-green-100 text-green-800' => $item['status'] === 'correct',
                                'bg-red-100 text-red-800' => $item['status'] === 'wrong',
                                'bg-gray-200 text-gray-700' => $item['status'] === 'skipped',
                            ])>{{ ucfirst($item['status']) }} · {{ (float) $item['marks'] > 0 ? '+' : '' }}{{ $item['marks'] }}</span>
                        </div>

                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($question->options as $option)
                                @php($picked = in_array($option->id, $item['selected'], true))
                                <li @class([
                                    'flex items-center gap-3 rounded-md border px-3 py-2',
                                    'border-green-300 bg-green-50' => $option->is_correct,
                                    'border-red-300 bg-red-50' => $picked && ! $option->is_correct,
                                    'border-gray-200' => ! $picked && ! $option->is_correct,
                                ])>
                                    <span class="w-5 font-semibold text-gray-500">{{ chr(64 + $loop->iteration) }}.</span>
                                    <span class="flex-1 whitespace-pre-line">{{ $option->option_text }}</span>
                                    @if ($picked)<span class="text-xs font-medium text-gray-600">Your answer</span>@endif
                                    @if ($option->is_correct)<span class="text-xs font-semibold text-green-700">Correct answer</span>@endif
                                </li>
                            @endforeach
                        </ul>

                        @if ($question->explanation)
                            <div class="mt-4 rounded-md bg-gray-50 p-4 text-sm">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Explanation</div>
                                <p class="mt-1 text-gray-700 whitespace-pre-line">{{ $question->explanation }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <a href="{{ route('student.dashboard') }}" class="inline-block text-sm text-gray-600 underline">← Back to dashboard</a>
        </div>
    </div>
</x-student-layout>
