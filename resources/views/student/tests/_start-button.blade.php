{{-- Start / Resume / Unlock / no attempts left.
     Expects $test (my_attempts_count, in-progress attempts and series loaded) and $access (App\Support\Access). --}}
@if ($test->attempts->isNotEmpty())
    {{-- Resuming is always allowed, even if access ended meanwhile. --}}
    <x-primary-link href="{{ route('student.attempts.show', $test->attempts->first()) }}" class="!bg-amber-600 hover:!bg-amber-500">Resume</x-primary-link>
@elseif (! $access->canAccessSeries($test->test_series_id))
    <x-primary-link href="{{ route('student.series.show', $test->series) }}" class="!bg-amber-600 hover:!bg-amber-500">🔒 Unlock · {{ $test->series->price }}</x-primary-link>
@elseif ($test->my_attempts_count < $test->max_attempts)
    <form method="POST" action="{{ route('student.attempts.store', $test) }}"
          onsubmit="this.querySelector('button').disabled = true">
        @csrf
        <x-primary-button>{{ $test->my_attempts_count > 0 ? 'Try again' : 'Start test' }}</x-primary-button>
    </form>
@else
    <span class="text-sm text-gray-500">No attempts left</span>
@endif
