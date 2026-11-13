<x-public-layout title="Features">
    <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6">
        <h1 class="font-display text-4xl font-extrabold tracking-tight">Features</h1>
        <p class="mt-4 text-lg text-gray-600">TestForge is an online exam and test-series platform. Here's what each kind of user gets.</p>

        @foreach ([
            'For students' => [
                ['One question at a time', 'A question navigator, a live countdown, and answers that save themselves.'],
                ['A server-side clock', 'When time is up, it is up — the server checks the deadline on every save.'],
                ['Clear results', 'Score, pass/fail, correct / wrong / skipped, time taken, and every answer with its explanation.'],
                ['Rank & percentile', 'Each test has a leaderboard of every student\'s best attempt.'],
                ['Subject analytics', 'Accuracy per subject, weakest first.'],
                ['Diagrams & maths', 'Questions and answer options can include images, formulas (LaTeX) and chemistry notation.'],
            ],
            'For teachers' => [
                ['Question bank', 'Single and multiple choice (decided automatically from the correct answers), difficulty, marks, negative marks, tags, explanations.'],
                ['Bulk import', 'Upload hundreds of questions from a CSV, with images in a ZIP. A preview shows every problem before anything is saved.'],
                ['Random test builder', 'Ask for "10 Algebra, 4 hard" — then swap, remove, reorder or edit what was picked.'],
                ['Analytics', 'Average score, pass rate, the top 10 and the toughest questions in each test.'],
            ],
            'For admins' => [
                ['People & content', 'Manage users and roles, subjects and topics.'],
                ['Paid series & passes', 'Sell test series (e.g. SSC, WBSC) and All-Access passes through Razorpay.'],
                ['Reports', 'Revenue, every test\'s analytics, and contact messages.'],
            ],
        ] as $group => $items)
            <h2 class="mt-14 font-display text-2xl font-bold">{{ $group }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($items as [$title, $text])
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <h3 class="flex items-center gap-2 font-semibold text-gray-900"><x-icon name="check" class="h-5 w-5 text-brand-600" /> {{ $title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach

        <h2 class="mt-14 font-display text-2xl font-bold">How scoring works</h2>
        <ul class="mt-4 list-disc space-y-2 ps-5 text-gray-700">
            <li>A correct answer earns the question's marks; a wrong answer loses its negative marks (if any); a skipped question scores 0.</li>
            <li>A question with one correct answer lets you choose one option. A question with several correct answers asks you to select all of them — and only scores if your selection is exactly right (no partial marks).</li>
            <li>Your leaderboard rank uses your best attempt: highest score first, then the fastest time.</li>
        </ul>
    </section>
</x-public-layout>
