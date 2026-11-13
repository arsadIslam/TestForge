<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Build test') }}: {{ $test->title }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ count($items) }} questions · {{ $test->total_marks }} total marks · pass {{ $test->pass_marks }}</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('teacher.tests.show', $test) }}" class="text-sm text-gray-600 underline">Test details</a>
                @can('publish', $test)
                    <form method="POST" action="{{ route('teacher.tests.publish', $test) }}"
                          onsubmit="return confirm('Publish this test? Its questions and settings will be locked.')">
                        @csrf
                        @method('PATCH')
                        <x-primary-button>Publish</x-primary-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- ===== Left: questions in this test (Alpine reorder) ===== --}}
            <section x-data="questionOrder({ items: @js($items) })" class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h3 class="font-semibold text-gray-800">In this test</h3>
                    <span x-show="dirty" x-cloak class="text-xs font-medium text-amber-700">Unsaved order</span>
                </div>

                <form method="POST" action="{{ route('teacher.tests.questions.reorder', $test) }}">
                    @csrf
                    @method('PUT')

                    <ol class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="item.id">
                            <li class="flex items-start gap-3 px-5 py-3 text-sm">
                                <input type="hidden" name="question_ids[]" :value="item.id">
                                <span class="w-6 pt-0.5 font-semibold text-gray-400" x-text="`${index + 1}.`"></span>
                                <div class="flex-1">
                                    <p class="text-gray-900" x-text="item.body"></p>
                                    <template x-if="item.image"><img :src="item.image" alt="Diagram" class="mt-2 h-12 w-12 rounded border border-gray-200 bg-white object-contain"></template>
                                    <p class="mt-1 text-xs text-gray-500" x-text="`${item.topic} · ${item.difficulty} · +${item.marks} / −${item.negative_marks}`"></p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="move(index, -1)" :disabled="index === 0"
                                            class="rounded px-2 py-1 text-gray-500 hover:bg-gray-100 disabled:opacity-30" aria-label="Move up">↑</button>
                                    <button type="button" @click="move(index, 1)" :disabled="index === items.length - 1"
                                            class="rounded px-2 py-1 text-gray-500 hover:bg-gray-100 disabled:opacity-30" aria-label="Move down">↓</button>
                                    <button type="button" @click="remove(item)"
                                            class="rounded px-2 py-1 text-red-600 hover:bg-red-50" aria-label="Remove">✕</button>
                                </div>
                            </li>
                        </template>
                    </ol>

                    <p x-show="items.length === 0" class="px-5 py-8 text-center text-sm text-gray-500">
                        No questions yet. Pick some from the question bank →
                    </p>

                    <div x-show="items.length > 1" class="border-t px-5 py-4">
                        <x-primary-button x-bind:disabled="!dirty" class="disabled:opacity-40">Save order</x-primary-button>
                    </div>
                </form>

                {{-- Lives outside the reorder form (forms can't be nested); questionOrder.remove() sets its action. --}}
                <form x-ref="removeForm" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </section>

            {{-- ===== Right: pick from the question bank ===== --}}
            <section class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b px-5 py-4">
                    <h3 class="font-semibold text-gray-800">Add from the question bank</h3>
                    <p class="text-xs text-gray-500">Only active questions that aren't already in this test are listed.</p>
                </div>

                <form method="GET" action="{{ route('teacher.tests.questions.index', $test) }}" class="flex flex-wrap items-end gap-3 border-b px-5 py-4">
                    <x-topic-select name="topic_id" class="text-sm" :subjects="$subjects" :selected="$filters['topic_id']" placeholder="All topics" aria-label="Topic" />
                    <x-select-input name="difficulty" class="text-sm" aria-label="Difficulty">
                        <option value="">Any difficulty</option>
                        @foreach ($difficulties as $difficulty)
                            <option value="{{ $difficulty->value }}" @selected($filters['difficulty'] === $difficulty->value)>{{ $difficulty->label() }}</option>
                        @endforeach
                    </x-select-input>
                    <x-select-input name="tag_id" class="text-sm" aria-label="Tag">
                        <option value="">Any tag</option>
                        @foreach ($tags as $id => $name)
                            <option value="{{ $id }}" @selected($filters['tag_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-secondary-button type="submit">Filter</x-secondary-button>
                </form>

                <form method="POST" action="{{ route('teacher.tests.questions.store', $test) }}">
                    @csrf
                    <ul class="divide-y divide-gray-100">
                        @forelse ($bank as $question)
                            <li>
                                <label class="flex cursor-pointer items-start gap-3 px-5 py-3 text-sm hover:bg-gray-50">
                                    <input type="checkbox" name="question_ids[]" value="{{ $question->id }}"
                                           class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <div class="flex-1">
                                        <p class="text-gray-900">{{ Str::limit($question->body, 110) }}</p>
                                        <x-question-image :src="$question->image_url" size="thumb" class="mt-2" alt="Diagram" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $question->topic->subject->name }} › {{ $question->topic->name }} · {{ $question->type->label() }} · +{{ $question->marks }}
                                        </p>
                                    </div>
                                    <x-difficulty-badge :difficulty="$question->difficulty" />
                                </label>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-gray-500">No more questions match these filters.</li>
                        @endforelse
                    </ul>

                    <x-input-error class="px-5 pt-3" :messages="array_merge($errors->get('question_ids'), ...array_values($errors->get('question_ids.*')))" />

                    @if ($bank->isNotEmpty())
                        <div class="flex items-center justify-between gap-3 border-t px-5 py-4">
                            <x-primary-button>Add selected</x-primary-button>
                            <div class="text-sm">{{ $bank->links('pagination::simple-tailwind') }}</div>
                        </div>
                    @endif
                </form>
            </section>
        </div>
    </div>
</x-teacher-layout>
