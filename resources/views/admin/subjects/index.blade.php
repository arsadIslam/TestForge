<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Subjects') }}</h2>
            <x-primary-link href="{{ route('admin.subjects.create') }}">New subject</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-right">Topics</th>
                            <th class="px-4 py-3 text-right">Questions</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($subjects as $subject)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $subject->name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $subject->slug }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.topics.index', ['subject_id' => $subject->id]) }}" class="text-indigo-600 hover:underline">{{ $subject->topics_count }}</a>
                                </td>
                                <td class="px-4 py-3 text-right">{{ $subject->questions_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                                              onsubmit="return confirm('Delete {{ addslashes($subject->name) }} and all its topics?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No subjects yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $subjects->links() }}
        </div>
    </div>
</x-admin-layout>
