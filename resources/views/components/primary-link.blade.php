{{-- An <a> styled like <x-primary-button>. (A <button> inside an <a> is invalid HTML.) --}}
<a {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2']) }}>
    {{ $slot }}
</a>
