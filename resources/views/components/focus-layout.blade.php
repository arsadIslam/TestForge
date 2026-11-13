{{-- Distraction-free shell for taking a test: no navigation, just the logo and the content. --}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts._head', ['title' => $title])
    </head>
    <body class="h-full bg-gray-50 font-sans text-gray-900 antialiased">
        <x-flash-messages />
        {{ $slot }}
    </body>
</html>
