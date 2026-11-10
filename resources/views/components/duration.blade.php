@props(['seconds'])
{{-- 1266 → "21m 06s" --}}
@if ($seconds === null)
    —
@else
    {{ intdiv((int) $seconds, 60) }}m {{ str_pad((string) ((int) $seconds % 60), 2, '0', STR_PAD_LEFT) }}s
@endif
