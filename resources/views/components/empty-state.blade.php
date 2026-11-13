@props(['icon' => 'inbox', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-lg bg-white px-6 py-12 text-center shadow-sm']) }}>
    <span class="rounded-full bg-gray-100 p-3 text-gray-400"><x-icon :name="$icon" class="h-6 w-6" /></span>
    <h3 class="mt-3 font-semibold text-gray-900">{{ $title }}</h3>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-gray-500">{{ $text }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
