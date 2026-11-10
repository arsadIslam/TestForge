<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $test->title }}</h2>
                <x-test-status-badge :status="$test->status" />
            </div>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-600 underline">← All reports</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('analytics._test')
        </div>
    </div>
</x-admin-layout>
