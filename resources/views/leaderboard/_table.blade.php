{{-- Expects $entries (from LeaderboardService) and optional $highlightUserId. --}}
<div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3 w-16">Rank</th>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3 text-right">Best score</th>
                <th class="px-4 py-3 text-right">Time</th>
                <th class="px-4 py-3 text-right">Percentile</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($entries as $entry)
                <tr @class(['bg-indigo-50 font-semibold' => ($highlightUserId ?? null) === $entry['user_id']])>
                    <td class="px-4 py-2">
                        <span @class([
                            'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                            'bg-yellow-100 text-yellow-800' => $entry['rank_no'] === 1,
                            'bg-gray-200 text-gray-700' => $entry['rank_no'] === 2,
                            'bg-orange-100 text-orange-800' => $entry['rank_no'] === 3,
                            'text-gray-500' => $entry['rank_no'] > 3,
                        ])>{{ $entry['rank_no'] }}</span>
                    </td>
                    <td class="px-4 py-2 text-gray-900">{{ $entry['name'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $entry['score'] }}</td>
                    <td class="px-4 py-2 text-right text-gray-600"><x-duration :seconds="$entry['time_taken_seconds']" /></td>
                    <td class="px-4 py-2 text-right text-gray-600">{{ number_format((float) $entry['percentile'], 1) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No results yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
