@props(['active'])

<a {{ $attributes->merge(['class' => ($active ?? false)
    ? 'inline-flex items-center border-b-2 border-brand-600 px-3 pt-1 text-sm font-semibold text-gray-900 transition'
    : 'inline-flex items-center border-b-2 border-transparent px-3 pt-1 text-sm font-medium text-gray-500 transition hover:border-gray-300 hover:text-gray-800']) }}
   @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
