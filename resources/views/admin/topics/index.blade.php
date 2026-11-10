<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Topics') }}</h2>
            <x-primary-link href="{{ route('admin.topics.create', ['subject_id' => $selectedSubjectId]) }}">New topic</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('admin.topics.index') }}" class="flex flex-wrap items-end gap-3 bg-white p-4 shadow-sm sm:rounded-lg">
                <div>
                    <x-input-label for="subject_id" value="Subject" />
                    <x-select-input id="subject_id" name="subject_id" class="mt-1 block" onchange="this.form.submit()">
                        <option value="">All subjects</option>
                        @foreach ($subjects as $id => $name)
                            <option value="{{ $id }}" @selected($selectedSubjectId === $id)>{{ $name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <noscript><x-primary-button>Filter</x-primary-button></noscript>
            </form>

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Topic</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-right">Questions</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topics as $topic)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $topic->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $topic->subject->name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $topic->slug }}</td>
                                <td class="px-4 py-3 text-right">{{ $topic->questions_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.topics.edit', $topic) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('admin.topics.destroy', $topic) }}"
                                              onsubmit="return confirm('Delete topic {{ addslashes($topic->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No topics found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $topics->links() }}
        </div>
    </div>
</x-admin-layout>
