{{-- Shared by the teacher analytics page and the admin report page. --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    <x-stat-card label="Average score" :value="$summary['avg_score'] ?? '—'" :hint="'out of '.$test->total_marks" />
    <x-stat-card label="Pass rate" :value="$summary['pass_rate'] !== null ? $summary['pass_rate'].'%' : '—'" hint="across all attempts" />
    <x-stat-card label="Students" :value="$summary['students']" :hint="$summary['attempts'].' attempt(s)'" />
    <x-stat-card label="Highest / lowest" :value="($summary['max_score'] ?? '—').' / '.($summary['min_score'] ?? '—')" />
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <section class="bg-white shadow-sm sm:rounded-lg">
        <div class="border-b px-6 py-4">
            <h3 class="font-semibold text-gray-800">Toughest questions</h3>
            <p class="text-xs text-gray-500">Lowest share of correct answers, among questions answered at least 10 times.</p>
        </div>
        <ol class="divide-y divide-gray-100">
            @forelse ($toughest as $row)
                <li class="px-6 py-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-gray-900">{{ Str::limit($row['body'], 90) }}</span>
                        <span class="shrink-0 font-semibold text-gray-700">{{ $row['correct_pct'] }}%</span>
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <x-percent-bar :value="$row['correct_pct']" />
                        <span class="shrink-0 text-xs text-gray-500">{{ ucfirst($row['difficulty']) }} · {{ $row['answered'] }} answers</span>
                    </div>
                </li>
            @empty
                <li class="px-6 py-8 text-center text-sm text-gray-500">Not enough answers yet (each question needs at least 10).</li>
            @endforelse
        </ol>
    </section>

    <section class="space-y-3">
        <h3 class="font-semibold text-gray-800">Top 10</h3>
        @include('leaderboard._table', ['entries' => $leaders, 'highlightUserId' => null])
    </section>
</div>

<section class="bg-white shadow-sm sm:rounded-lg">
    <div class="border-b px-6 py-4"><h3 class="font-semibold text-gray-800">All results</h3></div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Student</th>
                    <th class="px-4 py-3 text-right">Attempt</th>
                    <th class="px-4 py-3 text-right">Score</th>
                    <th class="px-4 py-3 text-right">Correct / wrong / skipped</th>
                    <th class="px-4 py-3 text-right">Time</th>
                    <th class="px-4 py-3">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($results as $result)
                    <tr>
                        <td class="px-4 py-2 text-gray-900">{{ $result->student->name }}</td>
                        <td class="px-4 py-2 text-right">#{{ $result->attempt->attempt_no }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $result->score }}</td>
                        <td class="px-4 py-2 text-right text-gray-600">{{ $result->correct_count }} / {{ $result->wrong_count }} / {{ $result->skipped_count }}</td>
                        <td class="px-4 py-2 text-right text-gray-600"><x-duration :seconds="$result->time_taken_seconds" /></td>
                        <td class="px-4 py-2">
                            <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-green-100 text-green-800' => $result->is_passed, 'bg-red-100 text-red-800' => ! $result->is_passed])>
                                {{ $result->is_passed ? 'Passed' : 'Not passed' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No results yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{ $results->links() }}
