<x-focus-layout :title="$attempt->test->title">
    <div x-data="testTaker({ questions: @js($questions), answers: @js($answers), secondsLeft: {{ $secondsLeft }} })" class="pb-12">

        {{-- Sticky bar: title, progress, timer --}}
        <div class="sticky top-0 z-10 border-b bg-white/95 backdrop-blur">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <x-logo size="sm" :wordmark="false" :animated="false" class="hidden sm:inline-flex" />
                    <div>
                    <h2 class="font-semibold text-gray-800">{{ $attempt->test->title }}</h2>
                    <p class="text-xs text-gray-500">
                        Attempt {{ $attempt->attempt_no }} ·
                        <span x-text="`${answeredCount} of ${questions.length} answered`"></span>
                    </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="rounded-md px-3 py-1 font-mono text-xl font-semibold tabular-nums"
                         :class="remaining <= 60 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-800'"
                         role="timer" aria-live="off" x-text="clock">--:--</div>
                    <x-primary-button type="button" @click="confirming = true" x-bind:disabled="submitting">Submit</x-primary-button>
                </div>
            </div>
            {{-- progress: share of questions answered --}}
            <div class="h-1 bg-gray-100">
                <div class="h-1 bg-brand-500 transition-all duration-300" :style="`width: ${questions.length ? answeredCount / questions.length * 100 : 0}%`"></div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto mt-6 grid grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">

            {{-- Current question --}}
            <section class="bg-white p-6 shadow-sm sm:rounded-lg lg:col-span-3" x-ref="questionCard"
                     x-effect="current; $nextTick(() => renderMath($refs.questionCard))">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span x-text="`Question ${current + 1} of ${questions.length}`"></span>
                    <span>
                        <span x-text="`+${question.marks}`"></span>
                        <template x-if="Number(question.negativeMarks) > 0">
                            <span class="text-red-600" x-text="` / −${question.negativeMarks}`"></span>
                        </template>
                    </span>
                </div>

                {{-- x-text escapes HTML, so question text can never inject markup --}}
                <p class="mt-4 text-lg text-gray-900 whitespace-pre-line" x-text="question.body"></p>

                <template x-if="question.image">
                    <img :src="question.image" :alt="`Diagram for question ${current + 1}`"
                         class="mt-4 max-h-96 max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                </template>

                {{-- Single choice (one correct option) → pick one; multiple (2+ correct) → pick all correct ones.
                     The number of correct options is never revealed. --}}
                <p class="mt-6 inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold"
                   :class="question.type === 'single' ? 'bg-gray-100 text-gray-700' : 'bg-indigo-100 text-indigo-800'">
                    <span class="inline-block h-3 w-3 border-2 border-current" :class="question.type === 'single' ? 'rounded-full' : 'rounded-sm'"></span>
                    <span x-text="question.type === 'single' ? 'Select one answer' : 'Select all correct answers'"></span>
                </p>

                {{-- Options with pictures are shown as a 2-column grid of cards; text-only options as a list. --}}
                <div class="mt-3" :class="question.hasOptionImages ? 'grid grid-cols-1 gap-3 sm:grid-cols-2' : 'space-y-3'">
                    <template x-for="(option, index) in question.options" :key="option.id">
                        <button type="button" @click="select(option.id)" :disabled="locked"
                                class="flex w-full items-start gap-3 rounded-md border px-4 py-3 text-left transition disabled:cursor-not-allowed"
                                :class="isSelected(option.id) ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                :aria-pressed="isSelected(option.id)">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center border text-xs"
                                  :class="[question.type === 'single' ? 'rounded-full' : 'rounded', isSelected(option.id) ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-400']"
                                  x-text="isSelected(option.id) ? '✓' : ''"></span>
                            <span class="font-semibold text-gray-500" x-text="String.fromCharCode(65 + index) + '.'"></span>
                            <span class="flex flex-1 flex-col gap-2">
                                <span class="whitespace-pre-line" x-show="option.text" x-text="option.text"></span>
                                <template x-if="option.image">
                                    <img :src="option.image" :alt="`Option ${String.fromCharCode(65 + index)}`"
                                         class="max-h-48 max-w-full rounded border border-gray-200 bg-white object-contain">
                                </template>
                            </span>
                        </button>
                    </template>
                </div>

                <div class="mt-6 flex items-center justify-between border-t pt-4">
                    <div class="flex items-center gap-3 text-sm">
                        <button type="button" @click="clear()" x-show="isAnswered(question)" :disabled="locked" class="text-gray-600 underline">Clear answer</button>
                        <span x-show="status[question.id] === 'saving' || status[question.id] === 'pending'" class="text-gray-500">Saving…</span>
                        <span x-show="status[question.id] === 'saved'" class="text-green-700">Saved ✓</span>
                        <span x-show="status[question.id] === 'error'" class="text-red-600" x-text="error"></span>
                    </div>
                    <div class="flex gap-2">
                        <x-secondary-button @click="go(current - 1)" x-bind:disabled="current === 0">← Previous</x-secondary-button>
                        <x-secondary-button @click="go(current + 1)" x-bind:disabled="current === questions.length - 1">Next →</x-secondary-button>
                    </div>
                </div>
            </section>

            {{-- Navigator --}}
            <aside class="bg-white p-5 shadow-sm sm:rounded-lg lg:self-start">
                <h3 class="text-sm font-semibold text-gray-700">Questions</h3>
                <div class="mt-3 grid grid-cols-5 gap-2">
                    <template x-for="(q, index) in questions" :key="q.id">
                        <button type="button" @click="go(index)" x-text="index + 1"
                                class="h-9 rounded-md text-sm font-medium"
                                :class="[
                                    isAnswered(q) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700',
                                    index === current ? 'ring-2 ring-indigo-500' : '',
                                    status[q.id] === 'error' ? '!bg-red-100 !text-red-700' : '',
                                ]"
                                :aria-label="`Question ${index + 1}${isAnswered(q) ? ', answered' : ''}`"></button>
                    </template>
                </div>
                <div class="mt-4 space-y-1 text-xs text-gray-500">
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-green-100"></span> Answered</div>
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-gray-100"></span> Not answered</div>
                </div>
            </aside>
        </div>

        {{-- Submit confirmation --}}
        <div x-show="confirming" x-cloak class="fixed inset-0 z-20 flex items-center justify-center bg-gray-900/50 p-4" @keydown.escape.window="confirming = false">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="confirming = false" role="dialog" aria-modal="true">
                <h3 class="text-lg font-semibold text-gray-900">Submit your test?</h3>
                <p class="mt-2 text-sm text-gray-600">
                    You have answered <strong x-text="answeredCount"></strong> of <strong x-text="questions.length"></strong> questions.
                    <span x-show="answeredCount < questions.length" class="text-amber-700">
                        <span x-text="questions.length - answeredCount"></span> unanswered question(s) will score 0.
                    </span>
                </p>
                <p class="mt-2 text-sm text-gray-600">You can't change your answers after submitting.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button @click="confirming = false">Keep working</x-secondary-button>
                    <x-primary-button type="button" @click="submit()">Yes, submit</x-primary-button>
                </div>
            </div>
        </div>

        {{-- Overlay while submitting (also shown when the timer hits zero) --}}
        <div x-show="submitting" x-cloak class="fixed inset-0 z-30 flex flex-col items-center justify-center gap-4 bg-white/90">
            <x-logo size="xl" :wordmark="false" loop />
            <p class="text-lg font-medium text-gray-700" x-text="remaining === 0 ? 'Time is up — submitting your test…' : 'Submitting your test…'"></p>
        </div>

        <form x-ref="submitForm" method="POST" action="{{ route('student.attempts.submit', $attempt) }}" class="hidden">
            @csrf
        </form>
    </div>
</x-focus-layout>
