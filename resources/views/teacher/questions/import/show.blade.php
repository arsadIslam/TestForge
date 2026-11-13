<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import preview') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if ($preview->fileErrors)
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">The file can't be imported:</p>
                    <ul class="mt-1 list-disc ps-5">@foreach ($preview->fileErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @else
                <div @class([
                    'flex flex-wrap items-center justify-between gap-3 rounded-md border p-4 text-sm',
                    'border-green-200 bg-green-50 text-green-900' => $preview->isValid(),
                    'border-amber-200 bg-amber-50 text-amber-900' => ! $preview->isValid(),
                ])>
                    <span>
                        <strong>{{ count($preview->rows) }}</strong> {{ Str::plural('row', count($preview->rows)) }} read —
                        <strong>{{ $preview->validCount() }}</strong> OK,
                        <strong>{{ $preview->invalidCount() }}</strong> with errors.
                        @unless ($preview->isValid())
                            Nothing is imported until every row is OK: fix the file and upload it again.
                        @endunless
                    </span>
                    @if ($preview->isValid())
                        <form method="POST" action="{{ route('teacher.questions.import.confirm', $import) }}" onsubmit="this.querySelector('button').disabled = true">
                            @csrf
                            <x-primary-button>Import {{ count($preview->rows) }} {{ Str::plural('question', count($preview->rows)) }}</x-primary-button>
                        </form>
                    @endif
                </div>
            @endif

            @if ($preview->rows)
                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Row</th>
                                <th class="px-4 py-3">Question</th>
                                <th class="px-4 py-3">Topic</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3 text-right">Options</th>
                                <th class="px-4 py-3">Correct</th>
                                <th class="px-4 py-3">Difficulty</th>
                                <th class="px-4 py-3 text-right">Marks</th>
                                <th class="px-4 py-3 text-right">Images</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 align-top">
                            @foreach ($preview->rows as $row)
                                <tr @class(['bg-red-50/60' => $row['errors']])>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $row['number'] }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-gray-900">{{ $row['summary']['body'] }}</span>
                                        @foreach ($row['errors'] as $error)
                                            <div class="mt-1 text-xs font-medium text-red-700">✕ {{ $error }}</div>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['summary']['topic'] }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['summary']['type'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ $row['summary']['options'] }}</td>
                                    <td class="px-4 py-2">{{ $row['summary']['correct'] ?: '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['summary']['difficulty'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ $row['summary']['marks'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ $row['summary']['images'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <a href="{{ route('teacher.questions.import.create') }}" class="text-sm text-indigo-600 hover:underline">← Upload a different file</a>
                <form method="POST" action="{{ route('teacher.questions.import.destroy', $import) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-gray-600 hover:underline">Cancel and delete this upload</button>
                </form>
            </div>
        </div>
    </div>
</x-teacher-layout>
