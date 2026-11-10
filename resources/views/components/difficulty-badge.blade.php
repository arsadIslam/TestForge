@props(['difficulty'])

<span @class([
    'rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-green-100 text-green-700' => $difficulty === \App\Enums\Difficulty::Easy,
    'bg-amber-100 text-amber-700' => $difficulty === \App\Enums\Difficulty::Medium,
    'bg-red-100 text-red-700' => $difficulty === \App\Enums\Difficulty::Hard,
])>{{ $difficulty->label() }}</span>
