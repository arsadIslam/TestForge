@props(['value'])
{{-- A thin horizontal bar for a 0–100 value: red below 40, amber below 70, green otherwise. --}}
@php($pct = max(0, min(100, (float) $value)))
<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-gray-100']) }}
     role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
    <div @class([
        'h-full rounded-full',
        'bg-red-500' => $pct < 40,
        'bg-amber-500' => $pct >= 40 && $pct < 70,
        'bg-green-500' => $pct >= 70,
    ]) style="width: {{ $pct }}%"></div>
</div>
