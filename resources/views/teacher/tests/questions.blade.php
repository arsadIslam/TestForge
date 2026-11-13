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
        {{-- ===== Auto-select: random questions by rules, then edit the result below ===== --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
            <section class="bg-white shadow-sm sm:rounded-lg"
                     x-data="testGenerator({ subjects: @js($subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'topics' => $s->topics->map->only('id', 'name')->values()])->values()), maxRules: {{ \App\Http\Requests\Teacher\GenerateTestQuestionsRequest::MAX_RULES }}, oldRules: @js(old('rules')) })">
                <details @if ($errors->has('rules') || $errors->has('rules.*') || count($items) === 0) open @endif>
                    <summary class="cursor-pointer px-5 py-4 font-semibold text-gray-800">🎲 Auto-select questions <span class="ms-2 text-sm font-normal text-gray-500">pick random questions by subject, topic and difficulty — then swap, remove or reorder them</span></summary>

                    <form method="POST" action="{{ route('teacher.tests.questions.generate', $test) }}" class="border-t px-5 py-4 space-y-3">
                        @csrf
                        <template x-for="(rule, index) in rules" :key="rule.key">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="w-6 text-gray-400" x-text="`${index + 1}.`"></span>
                                <x-select-input class="text-sm" x-bind:name="`rules[${index}][subject_id]`" x-model="rule.subject_id" @change="subjectChanged(rule)" required aria-label="Subject">
                                    <option value="">Subject…</option>
                                    <template x-for="subject in subjects" :key="subject.id"><option :value="subject.id" x-text="subject.name"></option></template>
                                </x-select-input>
                                <x-select-input class="text-sm" x-bind:name="`rules[${index}][topic_id]`" x-model="rule.topic_id" aria-label="Topic">
                                    <option value="">Any topic</option>
                                    <template x-for="topic in topicsFor(rule)" :key="topic.id"><option :value="topic.id" x-text="topic.name"></option></template>
                                </x-select-input>
                                <x-select-input class="text-sm" x-bind:name="`rules[${index}][difficulty]`" x-model="rule.difficulty" aria-label="Difficulty">
                                    <option value="">Any difficulty</option>
                                    @foreach ($difficulties as $difficulty)
                                        <option value="{{ $difficulty->value }}">{{ $difficulty->label() }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-text-input type="number" min="1" max="50" class="w-20 text-sm" x-bind:name="`rules[${index}][count]`" x-model.number="rule.count" required aria-label="How many" />
                                <span class="text-gray-500">questions</span>
                                <button type="button" @click="remove(index)" x-show="rules.length > 1" class="text-red-600 hover:underline">Remove</button>
                            </div>
                        </template>

                        <x-input-error :messages="array_merge($errors->get('rules'), $errors->get('mode'), ...array_values($errors->get('rules.*')))" />

                        <div class="flex flex-wrap items-center gap-4 pt-2 text-sm">
                            <x-secondary-button @click="add()" x-show="canAdd">+ Add rule</x-secondary-button>
                            <label class="inline-flex items-center gap-1"><input type="radio" name="mode" value="add" @checked(old('mode', 'add') === 'add') class="text-indigo-600"> Add to current questions</label>
                            <label class="inline-flex items-center gap-1"><input type="radio" name="mode" value="replace" @checked(old('mode') === 'replace') class="text-indigo-600"> Replace current questions</label>
                            <x-primary-button class="ms-auto" x-text="`Pick ${total} random question${total === 1 ? '' : 's'}`">Pick random questions</x-primary-button>
                        </div>
                    </form>
                </details>
            </section>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- ===== Left: questions in this test (Alpine reorder) ===== --}}
            <section x-data="questionOrder({ items: @js($items) })" class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h3 class="font-semibold text-gray-800">In this test</h3>
                    <span x-show="dirty" x-cloak class="text-xs font-medium text-amber-700">Unsaved order</span>
                </div>

                @if (count($items))
                    <div class="flex flex-wrap gap-2 border-b px-5 py-3 text-xs">
                        @foreach ($composition['byDifficulty'] as $label => $count)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">{{ $label }}: {{ $count }}</span>
                        @endforeach
                        <span class="text-gray-300">|</span>
                        @foreach ($composition['byTopic'] as $label => $count)
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-indigo-700">{{ $label }}: {{ $count }}</span>
                        @endforeach
                    </div>
                @endif

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
                                    <button type="button" @click="swap(item)" title="Swap for another random question (same topic and difficulty)"
                                            class="rounded px-2 py-1 text-indigo-600 hover:bg-indigo-50" aria-label="Swap">↻</button>
                                    <template x-if="item.editUrl">
                                        <a :href="item.editUrl" title="Edit this question" class="rounded px-2 py-1 text-gray-600 hover:bg-gray-100" aria-label="Edit">✎</a>
                                    </template>
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

                {{-- Live outside the reorder form (forms can't be nested); questionOrder sets their action. --}}
                <form x-ref="removeForm" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                <form x-ref="swapForm" method="POST" class="hidden">
                    @csrf
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
