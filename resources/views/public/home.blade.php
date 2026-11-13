<x-public-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu blur-3xl" aria-hidden="true">
            <div class="mx-auto aspect-[1155/678] w-[72rem] bg-gradient-to-tr from-brand-200 to-accent-200 opacity-40" style="clip-path: polygon(74% 44%, 100% 61%, 97% 26%, 85% 0%, 80% 2%, 72% 32%, 60% 62%, 52% 68%, 47% 58%, 45% 34%, 27% 76%, 0% 64%, 17% 100%, 27% 76%, 76% 97%, 74% 44%)"></div>
        </div>
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:py-28">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700"><x-icon name="sparkles" class="h-4 w-4" /> Exam practice that feels like the real thing</span>
                <h1 class="mt-6 font-display text-4xl font-extrabold tracking-tight text-gray-900 sm:text-6xl">
                    Forge your score,<br><span class="bg-gradient-to-r from-brand-600 to-accent-500 bg-clip-text text-transparent">one test at a time.</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-gray-600">
                    Timed mock tests with a clock the server enforces, instant scoring with negative marking,
                    your rank and percentile on every test, and the subjects you should practise next.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    @auth
                        <x-primary-link href="{{ route('dashboard') }}" class="!px-6 !py-3 !text-base">Go to your dashboard <x-icon name="arrow-right" class="h-5 w-5" /></x-primary-link>
                    @else
                        <x-primary-link href="{{ route('register') }}" class="!px-6 !py-3 !text-base">Start practising free <x-icon name="arrow-right" class="h-5 w-5" /></x-primary-link>
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">I have an account →</a>
                    @endauth
                </div>
            </div>
            <div class="flex justify-center">
                <div class="relative">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-brand-100 to-accent-100 blur-2xl"></div>
                    <x-logo size="xl" :wordmark="false" class="relative scale-150 p-10" />
                </div>
            </div>
        </div>
    </section>

    {{-- Live numbers (from the database) --}}
    <section class="border-y border-gray-100 bg-gray-50">
        <dl class="mx-auto grid max-w-6xl grid-cols-3 gap-6 px-4 py-10 text-center sm:px-6">
            @foreach (['questions' => 'questions in the bank', 'tests' => 'published tests', 'students' => 'students practising'] as $key => $label)
                <div>
                    <dt class="text-sm text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 font-display text-3xl font-bold text-gray-900 sm:text-4xl">{{ number_format($stats[$key]) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="max-w-2xl">
            <h2 class="font-display text-3xl font-bold tracking-tight">Everything a mock test should be</h2>
            <p class="mt-3 text-gray-600">Built for students preparing for competitive exams, and for the teachers who prepare them.</p>
        </div>
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['clock', 'A clock you can trust', 'The server decides when time is up — closing the tab or changing your computer\'s clock doesn\'t help or hurt.'],
                ['check', 'Answers saved as you go', 'Every click is saved instantly. Reload, lose Wi-Fi, come back: your answers are still there.'],
                ['bolt', 'Instant, fair scoring', 'Negative marking, all-or-nothing multiple choice, and a full review with explanations.'],
                ['chart', 'Rank & percentile', 'See where you stand on every test, ranked by your best attempt.'],
                ['academic', 'Know your weak spots', 'Accuracy by subject, weakest first — so you know what to practise next.'],
                ['clipboard', 'For teachers too', 'Question bank with images and maths, CSV import, and tests built in seconds from random picks.'],
            ] as [$icon, $title, $text])
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <span class="inline-flex rounded-lg bg-brand-50 p-2.5 text-brand-600"><x-icon :name="$icon" class="h-6 w-6" /></span>
                    <h3 class="mt-4 font-semibold text-gray-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-gradient-to-br from-brand-700 to-brand-900 text-white">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <h2 class="font-display text-3xl font-bold tracking-tight">How it works</h2>
            <ol class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach ([['Pick a test', 'Free tests are open to everyone; unlock a series like SSC or WBSC, or everything with an All-Access pass.'], ['Take it under exam conditions', 'One question at a time, a live countdown, and a navigator to jump around.'], ['Review and improve', 'Your score, rank, every correct answer with its explanation, and your weakest subjects.']] as $i => [$title, $text])
                    <li>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-accent-400 font-display text-lg font-bold text-brand-950">{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-lg font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-brand-100">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Test series (real data) --}}
    <section id="series" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6">
        <h2 class="font-display text-3xl font-bold tracking-tight">Test series</h2>
        <p class="mt-3 text-gray-600">Free tests need no payment. Paid series unlock their full set of mock tests.</p>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($series as $item)
                <div class="flex flex-col rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <h3 class="font-display text-xl font-bold text-gray-900">{{ $item->name }}</h3>
                        <span class="font-display text-xl font-bold text-brand-700">{{ $item->price }}</span>
                    </div>
                    <p class="mt-2 flex-1 text-sm text-gray-600">{{ $item->description }}</p>
                    <p class="mt-4 text-xs text-gray-500">{{ $item->tests_count }} {{ Str::plural('test', $item->tests_count) }} · access: {{ $item->validity_label }}</p>
                    <x-primary-link href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="mt-5">Get started</x-primary-link>
                </div>
            @empty
                <p class="text-gray-500">No paid series yet — every test is free.</p>
            @endforelse
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-t border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-3xl px-4 py-20 sm:px-6">
            <h2 class="text-center font-display text-3xl font-bold tracking-tight">Questions</h2>
            <div class="mt-10 divide-y divide-gray-200">
                @foreach ([
                    ['What happens if my internet drops during a test?', 'Your answers are saved after every click. When you reconnect, reload the page and carry on — the timer keeps the original deadline.'],
                    ['How is the score calculated?', 'A correct answer earns its marks; a wrong one can cost negative marks; a skipped question scores 0. On questions with several correct answers you must pick exactly the correct ones.'],
                    ['Do I have to pay?', 'No. Free tests are open to every registered student. Paid series and the All-Access pass unlock more tests.'],
                    ['Can I get a refund?', 'See our Refund & Cancellation policy — in short, unused purchases can be refunded within 7 days.'],
                ] as [$q, $a])
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-gray-900">
                            {{ $q }} <span class="text-gray-400 transition group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-gray-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6">
        <h2 class="font-display text-3xl font-bold tracking-tight">Ready for your next mock test?</h2>
        <p class="mt-3 text-gray-600">Create a free account in under a minute.</p>
        <x-primary-link href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="mt-8 !px-6 !py-3 !text-base">{{ auth()->check() ? 'Go to your dashboard' : 'Create your free account' }}</x-primary-link>
    </section>
</x-public-layout>
