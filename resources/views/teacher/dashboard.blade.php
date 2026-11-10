<x-teacher-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Welcome, :name', ['name' => Auth::user()->name]) }}</h2>
            <div class="flex gap-3">
                <x-primary-link href="{{ route('teacher.questions.create') }}">New question</x-primary-link>
                <x-primary-link href="{{ route('teacher.tests.create') }}">New test</x-primary-link>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                <x-stat-card label="My questions" :value="$questionCount" />
                @foreach (\App\Enums\TestStatus::cases() as $status)
                    <x-stat-card :label="$status->label().' tests'" :value="$testsByStatus[$status->value] ?? 0" />
                @endforeach
                <x-stat-card label="Results on my tests" :value="$resultCount" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b px-6 py-4">
                        <h3 class="font-semibold text-gray-800">Published, but no attempts yet</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @forelse ($untouched as $test)
                            <li class="flex items-center justify-between px-6 py-3">
                                <a href="{{ route('teacher.tests.show', $test['id']) }}" class="text-gray-900 hover:text-indigo-600">{{ $test['title'] }}</a>
                                <span class="text-xs text-gray-500">published {{ \Illuminate\Support\Carbon::parse($test['published_at'])->diffForHumans() }}</span>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-center text-gray-500">Every published test has been attempted.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b px-6 py-4"><h3 class="font-semibold text-gray-800">Latest results</h3></div>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @forelse ($recentResults as $result)
                            <li class="flex items-center justify-between px-6 py-3">
                                <span><span class="font-medium text-gray-900">{{ $result->student->name }}</span> <span class="text-gray-500">· {{ $result->test->title }}</span></span>
                                <span @class(['font-semibold', 'text-green-700' => $result->is_passed, 'text-red-700' => ! $result->is_passed])>{{ $result->score }}</span>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-center text-gray-500">No results yet.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>
    </div>
</x-teacher-layout>
