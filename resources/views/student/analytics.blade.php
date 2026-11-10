<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('My analytics') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Tests taken" :value="$summary['tests']" :hint="$summary['results'].' attempt(s)'" />
                <x-stat-card label="Average score" :value="($summary['avg_percent'] ?? '0').'%'" hint="of total marks" />
                <x-stat-card label="Best score" :value="($summary['best_percent'] ?? '0').'%'" />
                <x-stat-card label="Pass rate" :value="($summary['pass_rate'] ?? '0').'%'" />
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="border-b px-6 py-4">
                    <h3 class="font-semibold text-gray-800">Accuracy by subject</h3>
                    <p class="text-xs text-gray-500">Weakest subject first — that's where practice helps most. Skipped questions aren't counted.</p>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($subjects as $subject)
                        <li class="px-6 py-4">
                            <div class="flex items-baseline justify-between text-sm">
                                <span class="font-medium text-gray-900">
                                    {{ $subject['subject'] }}
                                    @if ($loop->first && count($subjects) > 1)
                                        <span class="ms-2 rounded bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-700">Weakest</span>
                                    @endif
                                </span>
                                <span class="text-gray-600">{{ $subject['accuracy'] }}% · {{ $subject['correct'] }}/{{ $subject['attempted'] }} correct</span>
                            </div>
                            <x-percent-bar class="mt-2" :value="$subject['accuracy']" />
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-500">Take a test to see your subject-wise accuracy.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-student-layout>
