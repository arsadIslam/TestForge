{{-- A question or option image. Renders nothing when there is no image. --}}
@props(['src' => null, 'alt' => 'Diagram', 'size' => 'lg'])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async"
         {{ $attributes->class([
             'rounded-lg border border-gray-200 bg-white object-contain',
             'max-h-96 max-w-full' => $size === 'lg',
             'max-h-40 max-w-full' => $size === 'md',
             'h-12 w-12' => $size === 'thumb',
         ]) }}>
@endif
