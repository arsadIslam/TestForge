@props(['status'])

<span @class([
    'rounded-full px-2 py-0.5 text-xs font-semibold',
    'bg-gray-200 text-gray-700' => $status === \App\Enums\TestStatus::Draft,
    'bg-green-100 text-green-700' => $status === \App\Enums\TestStatus::Published,
    'bg-amber-100 text-amber-800' => $status === \App\Enums\TestStatus::Archived,
])>{{ $status->label() }}</span>
