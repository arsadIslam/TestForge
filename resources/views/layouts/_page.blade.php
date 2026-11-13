{{-- Page header + flash messages + content, shared by the sidebar and top-nav shells. --}}
@isset($header)
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
            {{ $header }}
        </div>
    </header>
@endisset

<main>
    <x-flash-messages />

    {{ $slot }}
</main>
