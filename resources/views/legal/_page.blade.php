{{-- Shared wrapper for legal pages. Expects $title and $updated; content in $body (a section). --}}
<x-public-layout :title="$title">
    <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
        <p class="rounded-md border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-accent-900">
            <strong>Template text — review before going live.</strong> This page is a starting point, not legal advice. Have it checked for your business and jurisdiction.
        </p>
        <h1 class="mt-8 font-display text-4xl font-extrabold tracking-tight">{{ $title }}</h1>
        <p class="mt-2 text-sm text-gray-500">Last updated {{ $updated }}</p>
        <div class="mt-8 space-y-6 text-gray-700 leading-relaxed [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-gray-900 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:ps-5">
            @yield('body')
        </div>
    </article>
</x-public-layout>
