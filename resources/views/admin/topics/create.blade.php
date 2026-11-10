<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New topic') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.topics.store') }}" class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @include('admin.topics._form')
                <div class="flex items-center gap-4">
                    <x-primary-button>Create topic</x-primary-button>
                    <a href="{{ route('admin.topics.index') }}" class="text-sm text-gray-600 underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
