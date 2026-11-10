<x-teacher-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Question') }} #{{ $question->id }}</h2>
            <div class="flex items-center gap-3">
                @can('update', $question)
                    <x-primary-link href="{{ route('teacher.questions.edit', $question) }}">Edit</x-primary-link>
                @endcan
                @can('delete', $question)
                    <form method="POST" action="{{ route('teacher.questions.destroy', $question) }}" onsubmit="return confirm('Delete this question?')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Delete</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                    <span>{{ $question->topic->subject->name }} › {{ $question->topic->name }}</span>
                    <span>·</span>
                    <span>{{ $question->type->label() }}</span>
                    <x-difficulty-badge :difficulty="$question->difficulty" />
                    @unless ($question->is_active)
                        <span class="rounded bg-gray-200 px-1.5 text-xs text-gray-600">Inactive</span>
                    @endunless
                </div>

                {{-- {{ }} escapes HTML; whitespace-pre-line keeps the teacher's line breaks --}}
                <p class="text-lg text-gray-900 whitespace-pre-line">{{ $question->body }}</p>

                <ol class="space-y-2">
                    @foreach ($question->options as $option)
                        <li @class([
                            'flex items-start gap-3 rounded-md border px-4 py-2',
                            'border-green-300 bg-green-50' => $option->is_correct,
                            'border-gray-200' => ! $option->is_correct,
                        ])>
                            <span class="font-semibold text-gray-500">{{ chr(64 + $loop->iteration) }}.</span>
                            <span class="flex-1 whitespace-pre-line">{{ $option->option_text }}</span>
                            @if ($option->is_correct)
                                <span class="text-xs font-semibold text-green-700">Correct</span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($question->explanation)
                    <div class="rounded-md bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Explanation</div>
                        <p class="mt-1 text-gray-700 whitespace-pre-line">{{ $question->explanation }}</p>
                    </div>
                @endif
            </div>

            <dl class="grid grid-cols-2 gap-4 bg-white p-6 text-sm shadow-sm sm:rounded-lg sm:grid-cols-4">
                <div><dt class="text-gray-500">Marks</dt><dd class="font-medium">+{{ $question->marks }}</dd></div>
                <div><dt class="text-gray-500">Negative marks</dt><dd class="font-medium">−{{ $question->negative_marks }}</dd></div>
                <div><dt class="text-gray-500">Author</dt><dd class="font-medium">{{ $question->creator->name }}</dd></div>
                <div><dt class="text-gray-500">Used in tests</dt><dd class="font-medium">{{ $question->tests_count }}</dd></div>
                <div class="col-span-2 sm:col-span-4">
                    <dt class="text-gray-500">Tags</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($question->tags as $tag)
                            <span class="rounded bg-indigo-50 px-1.5 text-xs text-indigo-700">{{ $tag->name }}</span>
                        @empty
                            <span class="text-gray-400">None</span>
                        @endforelse
                    </dd>
                </div>
            </dl>

            @if ($locked)
                <p class="text-sm text-gray-500">This question is in a published test, so its options and correct answers are locked.</p>
            @endif

            <a href="{{ route('teacher.questions.index') }}" class="inline-block text-sm text-gray-600 underline">← Back to question bank</a>
        </div>
    </div>
</x-teacher-layout>
