<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'TestForge') }} · Online exams and test series</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <header class="max-w-5xl mx-auto flex items-center justify-between px-6 py-6">
            <span class="text-xl font-bold">{{ config('app.name', 'TestForge') }}</span>
            <nav class="flex items-center gap-4 text-sm font-medium">
                @auth
                    <x-primary-link href="{{ route('dashboard') }}">Dashboard</x-primary-link>
                @else
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Log in</a>
                    @if (Route::has('register'))
                        <x-primary-link href="{{ route('register') }}">Register</x-primary-link>
                    @endif
                @endauth
            </nav>
        </header>

        <main class="max-w-5xl mx-auto px-6 pb-16">
            <section class="py-12 sm:py-20">
                <h1 class="max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl">Timed tests, instant results, and a leaderboard.</h1>
                <p class="mt-5 max-w-2xl text-lg text-gray-600">
                    Teachers build question banks and timed tests. Students take them with a server-side timer and
                    auto-save, get scored automatically, and see their rank and their weakest subjects.
                </p>
            </section>

            <section class="grid gap-6 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="font-semibold">Teachers</h2>
                    <p class="mt-2 text-sm text-gray-600">Question bank with tags and difficulty, single and multiple choice, negative marking. Build a test, order its questions, publish.</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="font-semibold">Students</h2>
                    <p class="mt-2 text-sm text-gray-600">One question at a time, a countdown that the server enforces, answers saved as you go, and a full review with explanations.</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="font-semibold">Reports</h2>
                    <p class="mt-2 text-sm text-gray-600">Rank and percentile per test, subject-wise accuracy, and the toughest questions in every test.</p>
                </div>
            </section>

            {{-- Demo accounts exist only in seeded environments; never advertise them in production. --}}
            @unless (app()->isProduction())
                <section class="mt-10 rounded-lg border border-dashed border-gray-300 bg-white p-6 text-sm">
                    <h2 class="font-semibold">Demo logins <span class="font-normal text-gray-500">(password: <code>password</code>)</span></h2>
                    <ul class="mt-3 grid gap-2 sm:grid-cols-3">
                        <li><span class="text-gray-500">Admin</span><br><code>admin@testforge.test</code></li>
                        <li><span class="text-gray-500">Teacher</span><br><code>teacher1@testforge.test</code></li>
                        <li><span class="text-gray-500">Student</span><br><code>student1@testforge.test</code></li>
                    </ul>
                </section>
            @endunless
        </main>
    </body>
</html>
