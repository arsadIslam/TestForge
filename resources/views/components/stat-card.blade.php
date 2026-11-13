@props(['label', 'value', 'hint' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
        @if ($icon)
            <span class="rounded-lg bg-brand-50 p-2 text-brand-600"><x-icon :name="$icon" class="h-5 w-5" /></span>
        @endif
    </div>
    <div class="mt-1 font-display text-3xl font-bold tracking-tight text-gray-900">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-gray-500">{{ $hint }}</div>
    @endif
</div>
