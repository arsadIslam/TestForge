<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $test->title }}</h2>
                <x-test-status-badge :status="$test->status" />
            </div>
            @include('teacher.tests._actions')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($test->description)
                <p class="text-gray-700 whitespace-pre-line">{{ $test->description }}</p>
            @endif

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Questions" :value="$test->questions->count()" />
                <x-stat-card label="Total marks" :value="$test->total_marks" :hint="'Pass: '.$test->pass_marks" />
                <x-stat-card label="Duration" :value="$test->duration_minutes.' min'" :hint="$test->max_attempts.' attempt(s) allowed'" />
                <x-stat-card label="Attempts" :value="$test->attempts_count" />
            </div>

            <dl class="grid grid-cols-1 gap-4 bg-white p-6 text-sm shadow-sm sm:grid-cols-3 sm:rounded-lg">
                <div><dt class="text-gray-500">Opens</dt><dd class="font-medium">{{ $test->starts_at?->format('d M Y H:i') ?? 'Any time' }}</dd></div>
                <div><dt class="text-gray-500">Closes</dt><dd class="font-medium">{{ $test->ends_at?->format('d M Y H:i') ?? 'No end date' }}</dd></div>
                <div><dt class="text-gray-500">Published</dt><dd class="font-medium">{{ $test->published_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Series</dt><dd class="font-medium">{{ $test->series?->name ?? 'Free' }}</dd></div>
                <div><dt class="text-gray-500">Question order</dt><dd class="font-medium">{{ $test->shuffle_questions ? 'Shuffled per student' : 'Fixed' }}</dd></div>
                <div><dt class="text-gray-500">Times shown in</dt><dd class="font-medium">{{ config('app.timezone') }}</dd></div>
            </dl>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <h3 class="font-semibold text-gray-800">Questions</h3>
                    @can('update', $test)
                        <a href="{{ route('teacher.tests.questions.index', $test) }}" class="text-sm text-indigo-600 hover:underline">Add / reorder questions →</a>
                    @endcan
                </div>
                <ol class="divide-y divide-gray-100">
                    @forelse ($test->questions as $question)
                        <li class="flex items-start gap-4 px-6 py-3 text-sm">
                            <span class="w-6 font-semibold text-gray-400">{{ $question->pivot->sort_order }}.</span>
                            <div class="flex-1">
                                <a href="{{ route('teacher.questions.show', $question) }}" class="text-gray-900 hover:text-indigo-600">{{ Str::limit($question->body, 120) }}</a>
                                <x-question-image :src="$question->image_url" size="thumb" class="mt-2" alt="Diagram" />
                                <div class="mt-1 text-xs text-gray-500">{{ $question->topic->subject->name }} › {{ $question->topic->name }} · {{ $question->type->label() }}</div>
                            </div>
                            <x-difficulty-badge :difficulty="$question->difficulty" />
                            <span class="w-24 text-right text-gray-600">+{{ $question->pivot->marks }} / −{{ $question->pivot->negative_marks }}</span>
                        </li>
                    @empty
                        <li class="px-6 py-6 text-center text-gray-500">No questions yet.</li>
                    @endforelse
                </ol>
            </div>

            <a href="{{ route('teacher.tests.index') }}" class="inline-block text-sm text-gray-600 underline">← Back to my tests</a>
        </div>
    </div>
</x-teacher-layout>
