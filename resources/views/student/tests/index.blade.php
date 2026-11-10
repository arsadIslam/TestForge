<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Available tests') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @forelse ($tests as $test)
                <div class="flex flex-col gap-4 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:rounded-lg">
                    <div>
                        <a href="{{ route('student.tests.show', $test->slug) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">{{ $test->title }}</a>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $test->questions_count }} questions · {{ $test->duration_minutes }} min · {{ $test->total_marks }} marks (pass {{ $test->pass_marks }})
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Attempts used: {{ $test->my_attempts_count }} / {{ $test->max_attempts }}
                            @if ($test->ends_at)
                                · Closes {{ $test->ends_at->format('d M Y, H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0">@include('student.tests._start-button')</div>
                </div>
            @empty
                <div class="bg-white p-8 text-center text-gray-500 shadow-sm sm:rounded-lg">No tests are open right now. Check back later.</div>
            @endforelse

            {{ $tests->links() }}
        </div>
    </div>
</x-student-layout>
