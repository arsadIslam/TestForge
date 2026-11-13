<x-public-layout title="Contact">
    <section class="mx-auto grid max-w-5xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <h1 class="font-display text-4xl font-extrabold tracking-tight">Contact us</h1>
            <p class="mt-4 text-gray-600">Questions about a test, a payment or your account? Send us a message and we'll reply by email, usually within two working days.</p>
            <ul class="mt-8 space-y-4 text-sm text-gray-700">
                <li class="flex items-center gap-3"><x-icon name="envelope" class="h-5 w-5 text-brand-600" /> {{ config('mail.from.address') }}</li>
                <li class="flex items-center gap-3"><x-icon name="document" class="h-5 w-5 text-brand-600" /> <a href="{{ route('legal.refunds') }}" class="underline">Refund &amp; Cancellation policy</a></li>
            </ul>
        </div>

        <form method="POST" action="{{ route('contact.store') }}" class="space-y-5 rounded-lg bg-white p-6 shadow-sm lg:col-span-3">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Your name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" maxlength="100" required :value="old('name', auth()->user()?->name)" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" maxlength="150" required :value="old('email', auth()->user()?->email)" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>
            </div>
            <div>
                <x-input-label for="subject" value="Subject" />
                <x-text-input id="subject" name="subject" class="mt-1 block w-full" maxlength="150" required :value="old('subject')" />
                <x-input-error class="mt-2" :messages="$errors->get('subject')" />
            </div>
            <div>
                <x-input-label for="message" value="Message" />
                <textarea id="message" name="message" rows="6" required minlength="10" maxlength="5000"
                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('message') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('message')" />
            </div>
            {{-- Honeypot: invisible to people (and screen readers), only bots fill it in. --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Leave this empty</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <x-primary-button>Send message</x-primary-button>
        </form>
    </section>
</x-public-layout>
