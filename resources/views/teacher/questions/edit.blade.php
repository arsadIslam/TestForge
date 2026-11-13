<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit question') }} #{{ $question->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('teacher.questions.update', $question) }}" enctype="multipart/form-data" class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @method('PUT')
                @include('teacher.questions._form')
                <div class="flex items-center gap-4 border-t pt-6">
                    <x-primary-button>Save changes</x-primary-button>
                    <a href="{{ route('teacher.questions.show', $question) }}" class="text-sm text-gray-600 underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-teacher-layout>
