<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import questions') }}</h2>
            <a href="{{ route('teacher.questions.import.template') }}" class="text-sm font-medium text-indigo-600 hover:underline">Download the CSV template ↓</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ route('teacher.questions.import.store') }}" enctype="multipart/form-data"
                  class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                <div>
                    <x-input-label for="csv" value="Questions (.csv)" />
                    <input id="csv" name="csv" type="file" accept=".csv,text/csv" required
                           class="mt-1 block w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-medium file:text-indigo-700">
                    <x-input-error class="mt-2" :messages="$errors->get('csv')" />
                    <p class="mt-1 text-xs text-gray-500">Up to {{ $maxRows }} questions, max 2 MB, UTF-8 (in Excel: Save as → CSV UTF-8).</p>
                </div>
                <div>
                    <x-input-label for="zip" value="Images (.zip, optional)" />
                    <input id="zip" name="zip" type="file" accept=".zip,application/zip"
                           class="mt-1 block w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:font-medium file:text-gray-700">
                    <x-input-error class="mt-2" :messages="$errors->get('zip')" />
                    <p class="mt-1 text-xs text-gray-500">Only needed if the CSV's <code>image</code> / <code>option_N_image</code> columns name files. JPG, PNG or WebP, each ≤ 2 MB; the ZIP ≤ 20 MB.</p>
                </div>
                <div class="flex items-center gap-4">
                    <x-primary-button>Upload and preview</x-primary-button>
                    <span class="text-sm text-gray-500">Nothing is saved until you confirm the preview.</span>
                </div>
            </form>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg text-sm">
                <h3 class="font-semibold text-gray-800">How to fill in the CSV</h3>
                <table class="mt-3 w-full text-left">
                    <tbody class="divide-y divide-gray-100 align-top">
                        <tr><td class="py-2 pe-4 font-mono text-xs">subject, topic</td><td class="py-2 text-gray-600">Names exactly as they exist on the site (case doesn't matter). <strong>Required.</strong></td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">body</td><td class="py-2 text-gray-600">The question. LaTeX works: <code>\( x^2 \)</code>, <code>$$ \frac{a}{b} $$</code>. <strong>Required.</strong></td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">option_1 … option_6</td><td class="py-2 text-gray-600">At least two. An option can be text, an image (<code>option_N_image</code>), or both.</td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">correct</td><td class="py-2 text-gray-600">Number(s) of the correct option(s): <code>2</code> or <code>1|3</code>. One number → single choice; two or more → students select all correct answers. <strong>Required.</strong></td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">difficulty</td><td class="py-2 text-gray-600"><code>easy</code>, <code>medium</code> (default) or <code>hard</code>.</td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">marks, negative_marks</td><td class="py-2 text-gray-600">Default 1 and 0. Negative marks can't exceed marks.</td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">tags</td><td class="py-2 text-gray-600">Separated by <code>|</code>, e.g. <code>Important|Previous Year</code>. New tags are created.</td></tr>
                        <tr><td class="py-2 pe-4 font-mono text-xs">explanation, image</td><td class="py-2 text-gray-600">Optional. <code>image</code> = a file name inside the ZIP.</td></tr>
                    </tbody>
                </table>

                <details class="mt-4">
                    <summary class="cursor-pointer font-medium text-gray-700">Subjects and topics on this site</summary>
                    <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($subjects as $subject)
                            <li><span class="font-medium">{{ $subject->name }}:</span> <span class="text-gray-600">{{ $subject->topics->pluck('name')->implode(', ') }}</span></li>
                        @endforeach
                    </ul>
                </details>
            </div>
        </div>
    </div>
</x-teacher-layout>
