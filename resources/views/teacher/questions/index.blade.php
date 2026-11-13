<x-teacher-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Question bank') }}</h2>
            <x-primary-link href="{{ route('teacher.questions.create') }}">New question</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('teacher.questions.index') }}" class="flex flex-wrap items-end gap-3 bg-white p-4 shadow-sm sm:rounded-lg">
                <div>
                    <x-input-label for="topic_id" value="Topic" />
                    <x-topic-select id="topic_id" name="topic_id" class="mt-1 block" :subjects="$subjects" :selected="$filters['topic_id']" placeholder="All topics" />
                </div>
                <div>
                    <x-input-label for="difficulty" value="Difficulty" />
                    <x-select-input id="difficulty" name="difficulty" class="mt-1 block">
                        <option value="">Any</option>
                        @foreach ($difficulties as $difficulty)
                            <option value="{{ $difficulty->value }}" @selected($filters['difficulty'] === $difficulty->value)>{{ $difficulty->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="tag_id" value="Tag" />
                    <x-select-input id="tag_id" name="tag_id" class="mt-1 block">
                        <option value="">Any</option>
                        @foreach ($tags as $id => $name)
                            <option value="{{ $id }}" @selected($filters['tag_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <x-primary-button>Filter</x-primary-button>
                @if (array_filter($filters))
                    <a href="{{ route('teacher.questions.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                @endif
                <span class="ms-auto text-sm text-gray-500">{{ $questions->total() }} {{ Str::plural('question', $questions->total()) }}</span>
            </form>

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Question</th>
                            <th class="px-4 py-3">Topic</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Difficulty</th>
                            <th class="px-4 py-3 text-right">Marks</th>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($questions as $question)
                            <tr @class(['bg-gray-50 text-gray-400' => ! $question->is_active])>
                                <td class="px-4 py-3 max-w-md">
                                    <a href="{{ route('teacher.questions.show', $question) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ Str::limit($question->body, 90) }}
                                    </a>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @if ($question->image_path)
                                            <span class="rounded bg-sky-50 px-1.5 text-xs text-sky-700">Image</span>
                                        @endif
                                        @unless ($question->is_active)
                                            <span class="rounded bg-gray-200 px-1.5 text-xs text-gray-600">Inactive</span>
                                        @endunless
                                        @foreach ($question->tags as $tag)
                                            <span class="rounded bg-indigo-50 px-1.5 text-xs text-indigo-700">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $question->topic->name }}
                                    <div class="text-xs text-gray-400">{{ $question->topic->subject->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $question->type->label() }}</td>
                                <td class="px-4 py-3"><x-difficulty-badge :difficulty="$question->difficulty" /></td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    +{{ $question->marks }}
                                    @if ((float) $question->negative_marks > 0)
                                        <span class="text-red-600">/ −{{ $question->negative_marks }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $question->creator->id === Auth::id() ? 'You' : $question->creator->name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('teacher.questions.show', $question) }}" class="text-gray-600 hover:underline">View</a>
                                        @can('update', $question)
                                            <a href="{{ route('teacher.questions.edit', $question) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No questions match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $questions->links() }}
        </div>
    </div>
</x-teacher-layout>
