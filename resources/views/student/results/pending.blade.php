<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $attempt->test->title }}</h2>
    </x-slot>

    {{-- Reload every 5 s until the EvaluateAttempt job has scored the attempt. --}}
    <div class="py-12" x-data x-init="setTimeout(() => window.location.reload(), 5000)">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-8 text-center shadow-sm sm:rounded-lg">
                <x-logo size="xl" :wordmark="false" loop class="mx-auto" />
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Your answers are being marked…</h3>
                <p class="mt-2 text-sm text-gray-600">This page refreshes by itself. It usually takes a few seconds.</p>
                <a href="{{ route('student.dashboard') }}" class="mt-6 inline-block text-sm text-gray-600 underline">Back to dashboard</a>
            </div>
        </div>
    </div>
</x-student-layout>
