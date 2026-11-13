@props(['active'])

<a {{ $attributes->merge(['class' => ($active ?? false)
    ? 'block w-full border-l-4 border-brand-600 bg-brand-50 py-2 pe-4 ps-3 text-start text-base font-semibold text-brand-700'
    : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800']) }}>
    {{ $slot }}
</a>
