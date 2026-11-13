<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $series->name }}</h2>
                <p class="text-sm text-gray-500">{{ $series->price }} · access: {{ $series->validity_label }}</p>
            </div>
            @if ($access->canAccessSeries($series->id))
                <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                    ✓ {{ $access->ownsSeries($series->id) ? 'Unlocked' : 'Included in All Access' }}
                </span>
            @elseif ($series->is_active)
                @include('student.series._buy', ['type' => 'series', 'id' => $series->id, 'label' => 'Buy for '.$series->price])
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if ($series->description)
                <p class="text-gray-700 whitespace-pre-line">{{ $series->description }}</p>
            @endif

            @forelse ($tests as $test)
                <div class="flex flex-col gap-3 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:rounded-lg">
                    <div>
                        <span class="font-semibold text-gray-900">{{ $test->title }}</span>
                        <p class="mt-1 text-sm text-gray-600">{{ $test->questions_count }} questions · {{ $test->duration_minutes }} min · {{ $test->total_marks }} marks</p>
                    </div>
                    <div class="shrink-0">@include('student.tests._start-button')</div>
                </div>
            @empty
                <div class="bg-white p-8 text-center text-gray-500 shadow-sm sm:rounded-lg">No tests published in this series yet.</div>
            @endforelse

            @if (! $access->canAccessSeries($series->id) && $plans->isNotEmpty())
                <p class="text-sm text-gray-600">Or get every series with an <a href="{{ route('student.series.index') }}" class="font-medium text-indigo-600 underline">All-Access pass</a> from {{ $plans->first()->price }}.</p>
            @endif
        </div>
    </div>
</x-student-layout>
