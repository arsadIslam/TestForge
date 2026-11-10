<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $test->title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($test->description)
                <p class="text-gray-700 whitespace-pre-line">{{ $test->description }}</p>
            @endif

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Questions" :value="$test->questions_count" />
                <x-stat-card label="Duration" :value="$test->duration_minutes.' min'" />
                <x-stat-card label="Total marks" :value="$test->total_marks" :hint="'Pass: '.$test->pass_marks" />
                <x-stat-card label="Attempts" :value="$test->my_attempts_count.' / '.$test->max_attempts" hint="used / allowed" />
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-gray-800">Before you start</h3>
                <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-gray-700">
                    <li>The timer starts when you click <strong>Start test</strong> and keeps running if you close the page.</li>
                    <li>Your answers are saved automatically as you go.</li>
                    <li>Some questions have <strong>negative marks</strong> for a wrong answer — an unanswered question scores 0.</li>
                    <li>Multiple-choice questions only score if you select <strong>all</strong> the correct options and nothing else.</li>
                    <li>When time runs out, your test is submitted automatically.</li>
                </ul>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('student.tests.index') }}" class="text-sm text-gray-600 underline">← All tests</a>
                @include('student.tests._start-button')
            </div>
        </div>
    </div>
</x-student-layout>
