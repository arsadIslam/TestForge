{{-- Breeze auth pages (login, register, reset…): a brand panel + the form. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts._head')
    </head>
    <body class="h-full bg-white font-sans text-gray-900 antialiased">
        <div class="flex min-h-full">
            {{-- Brand panel (large screens) --}}
            <div class="relative hidden w-0 flex-1 overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950 lg:block">
                <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-accent-400/20 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-brand-400/30 blur-3xl"></div>
                <div class="relative flex h-full flex-col justify-between p-12 text-white">
                    <a href="{{ route('home') }}"><x-logo light /></a>
                    <div>
                        <x-logo size="xl" :wordmark="false" light />
                        <h2 class="mt-8 max-w-md font-display text-4xl font-bold leading-tight">Practise under real exam conditions.</h2>
                        <p class="mt-4 max-w-md text-brand-100">Timed tests with a server-checked clock, instant scoring, your rank and percentile, and the subjects to work on next.</p>
                    </div>
                    <p class="text-sm text-brand-200">© {{ date('Y') }} {{ config('app.name') }}</p>
                </div>
            </div>

            {{-- Form --}}
            <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:flex-none lg:px-20 xl:px-24">
                <div class="mx-auto w-full max-w-sm">
                    <a href="{{ route('home') }}" class="lg:hidden"><x-logo /></a>
                    <div class="mt-8 lg:mt-0">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
