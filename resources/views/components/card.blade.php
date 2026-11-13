{{-- A white card. Optional `title` slot/prop and `padding` (default true). --}}
@props(['title' => null, 'padding' => true])

<section {{ $attributes->merge(['class' => 'rounded-lg bg-white shadow-sm']) }}>
    @if ($title)
        <div class="border-b border-gray-100 px-6 py-4"><h3 class="font-semibold text-gray-900">{{ $title }}</h3></div>
    @endif
    <div @class(['p-6' => $padding])>{{ $slot }}</div>
</section>
