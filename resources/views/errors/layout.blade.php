{{-- Error pages: deliberately simple (no database, no user needed) so they render even when things break. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts._head', ['title' => $title])
    </head>
    <body class="flex h-full items-center justify-center bg-gray-50 px-6 font-sans text-gray-900 antialiased">
        <main class="max-w-md text-center">
            <a href="{{ url('/') }}" class="inline-flex"><x-logo size="lg" :wordmark="false" /></a>
            <p class="mt-6 font-display text-6xl font-extrabold text-brand-600">{{ $code }}</p>
            <h1 class="mt-2 font-display text-2xl font-bold">{{ $title }}</h1>
            <p class="mt-3 text-gray-600">{{ $message }}</p>
            <div class="mt-8 flex items-center justify-center gap-4">
                <x-primary-link href="{{ url('/dashboard') }}">Go to my dashboard</x-primary-link>
                <a href="{{ url('/') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Home</a>
            </div>
        </main>
    </body>
</html>
