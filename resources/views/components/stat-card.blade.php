@props(['label', 'value', 'hint' => null])

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm sm:rounded-lg p-5']) }}>
    <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-gray-500">{{ $hint }}</div>
    @endif
</div>
