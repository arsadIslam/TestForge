<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Leaderboard') }}: {{ $test->title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if ($myRank)
                <div class="rounded-md border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                    You are <strong>#{{ $myRank['rank_no'] }}</strong> of {{ $myRank['total_students'] }} with a best score of
                    <strong>{{ $myRank['score'] }}</strong> — better than {{ number_format((float) $myRank['percentile'], 1) }}% of students.
                </div>
            @else
                <div class="rounded-md border border-gray-200 bg-white p-4 text-sm text-gray-600">You haven't got a result in this test yet.</div>
            @endif

            @include('leaderboard._table', ['entries' => $entries, 'highlightUserId' => Auth::id()])

            <p class="text-xs text-gray-500">
                Each student is ranked by their best attempt (highest score, then fastest time). Equal results share a rank.
                Showing the top {{ \App\Services\LeaderboardService::TOP }}; updated within 5 minutes of a new result.
            </p>
        </div>
    </div>
</x-student-layout>
