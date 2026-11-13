@props(['color' => 'gray'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
    'bg-gray-100 text-gray-700' => $color === 'gray',
    'bg-brand-100 text-brand-700' => $color === 'brand',
    'bg-accent-100 text-accent-800' => $color === 'accent',
    'bg-green-100 text-green-800' => $color === 'green',
    'bg-red-100 text-red-800' => $color === 'red',
]) }}>{{ $slot }}</span>
