{{-- Public pages: header with the animated logo, footer with the legal links (needed for Razorpay). --}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        @include('layouts._head', ['title' => $title])
    </head>
    <body class="flex min-h-full flex-col bg-white font-sans text-gray-900 antialiased">
        <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-gray-100 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" aria-label="{{ config('app.name') }} home"><x-logo /></a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-gray-600 md:flex" aria-label="Main">
                    <a href="{{ route('features') }}" @class(['hover:text-gray-900', 'text-gray-900' => request()->routeIs('features')])>Features</a>
                    <a href="{{ route('home') }}#series" class="hover:text-gray-900">Test series</a>
                    <a href="{{ route('contact') }}" @class(['hover:text-gray-900', 'text-gray-900' => request()->routeIs('contact')])>Contact</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <x-primary-link href="{{ route('dashboard') }}">Dashboard</x-primary-link>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Log in</a>
                        <x-primary-link href="{{ route('register') }}">Start free</x-primary-link>
                    @endauth
                </div>

                <button type="button" @click="open = ! open" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="Menu">
                    <x-icon name="menu" class="h-6 w-6" />
                </button>
            </div>

            <div x-show="open" x-cloak class="border-t border-gray-100 px-4 py-3 md:hidden">
                <a href="{{ route('features') }}" class="block py-2 text-sm font-medium text-gray-700">Features</a>
                <a href="{{ route('home') }}#series" class="block py-2 text-sm font-medium text-gray-700">Test series</a>
                <a href="{{ route('contact') }}" class="block py-2 text-sm font-medium text-gray-700">Contact</a>
                <div class="mt-2 flex gap-3">
                    @auth
                        <x-primary-link href="{{ route('dashboard') }}">Dashboard</x-primary-link>
                    @else
                        <a href="{{ route('login') }}" class="py-2 text-sm font-semibold text-gray-700">Log in</a>
                        <x-primary-link href="{{ route('register') }}">Start free</x-primary-link>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1">
            <x-flash-messages />
            {{ $slot }}
        </main>

        <footer class="border-t border-gray-100 bg-gray-50">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <x-logo :animated="false" />
                    <p class="mt-3 max-w-sm text-sm text-gray-600">Online exams and test series: timed tests, instant results, your rank and the subjects to practise next.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Product</h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-600">
                        <li><a href="{{ route('features') }}" class="hover:text-gray-900">Features</a></li>
                        <li><a href="{{ route('home') }}#series" class="hover:text-gray-900">Test series</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-gray-900">Create an account</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Company</h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-600">
                        <li><a href="{{ route('contact') }}" class="hover:text-gray-900">Contact</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="hover:text-gray-900">Terms of Service</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="hover:text-gray-900">Privacy Policy</a></li>
                        <li><a href="{{ route('legal.refunds') }}" class="hover:text-gray-900">Refund &amp; Cancellation</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-200 py-6 text-center text-xs text-gray-500">© {{ date('Y') }} {{ config('app.name') }}. Payments are processed by Razorpay.</div>
        </footer>
    </body>
</html>
