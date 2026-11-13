<x-student-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Test series & passes') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <section>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Test series</h3>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($series as $item)
                        <div class="flex flex-col rounded-lg bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('student.series.show', $item) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">{{ $item->name }}</a>
                                <span class="text-lg font-bold text-gray-900">{{ $item->price }}</span>
                            </div>
                            <p class="mt-1 flex-1 text-sm text-gray-600">{{ $item->description }}</p>
                            <p class="mt-3 text-xs text-gray-500">{{ $item->tests_count }} {{ Str::plural('test', $item->tests_count) }} · access: {{ $item->validity_label }}</p>
                            <div class="mt-4">
                                @if ($access->ownsSeries($item->id))
                                    <span class="text-sm font-semibold text-green-700">✓ Owned{{ $access->seriesUntil($item->id) ? ' until '.$access->seriesUntil($item->id)->format('d M Y') : ' for life' }}</span>
                                @elseif ($access->hasAllAccess())
                                    <span class="text-sm font-semibold text-indigo-700">✓ Included in your All Access</span>
                                @else
                                    @include('student.series._buy', ['type' => 'series', 'id' => $item->id, 'label' => 'Buy for '.$item->price])
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No paid series yet — every test is free.</p>
                    @endforelse
                </div>
            </section>

            @if ($plans->isNotEmpty())
                <section>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">All-Access passes</h3>
                    <p class="mt-1 text-sm text-gray-600">One payment unlocks <strong>every</strong> test in every series. Buying again while a pass is active extends it.</p>
                    @if ($access->hasAllAccess())
                        <p class="mt-2 text-sm font-semibold text-indigo-700">✨ Your All Access runs until {{ $access->allAccessUntil->format('d M Y') }}.</p>
                    @endif
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        @foreach ($plans as $plan)
                            <div class="rounded-lg border-2 border-indigo-100 bg-white p-5 shadow-sm">
                                <div class="font-semibold text-gray-900">{{ $plan->name }}</div>
                                <div class="mt-2 text-3xl font-bold text-gray-900">{{ $plan->price }}</div>
                                <div class="text-xs text-gray-500">{{ $plan->duration_days }} days of every test</div>
                                <div class="mt-4">@include('student.series._buy', ['type' => 'plan', 'id' => $plan->id, 'label' => $access->hasAllAccess() ? 'Extend' : 'Buy pass'])</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-student-layout>
