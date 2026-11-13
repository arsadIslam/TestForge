{{--
    The TestForge logo: an anvil, a hammer strike, sparks, and a forged ✓ (CSS in app.css).
    Plays once on load and again on hover; `loop` keeps forging (loading states);
    `animated=false` and "reduce motion" show the finished logo, perfectly still.
--}}
@props(['size' => 'md', 'wordmark' => true, 'animated' => true, 'loop' => false, 'light' => false])

@php
    $px = ['sm' => 28, 'md' => 36, 'lg' => 56, 'xl' => 120][$size] ?? 36;
    $text = ['sm' => 'text-lg', 'md' => 'text-xl', 'lg' => 'text-3xl', 'xl' => 'text-5xl'][$size] ?? 'text-xl';
@endphp

<span {{ $attributes->class(['tf-logo inline-flex items-center gap-2 select-none', 'tf-logo--animate' => $animated && ! $loop, 'tf-logo--loop' => $loop]) }}>
    <svg width="{{ $px }}" height="{{ $px }}" viewBox="0 0 48 48" role="img" aria-label="{{ config('app.name', 'TestForge') }}" xmlns="http://www.w3.org/2000/svg">
        {{-- sparks fly out from the point of impact --}}
        <g fill="#fbbf24">
            <circle class="tf-spark" cx="15" cy="24" r="1.6" style="--dx: -9px; --dy: -7px" />
            <circle class="tf-spark" cx="15" cy="24" r="1.2" style="--dx: -3px; --dy: -11px" />
            <circle class="tf-spark" cx="15" cy="24" r="1.4" style="--dx: 5px; --dy: -10px" />
            <circle class="tf-spark" cx="15" cy="24" r="1.1" style="--dx: 10px; --dy: -5px" />
            <circle class="tf-spark" cx="15" cy="24" r="1" style="--dx: -11px; --dy: -1px" />
        </g>
        {{-- the forged check mark --}}
        <path class="tf-check" d="M13 14.5l5.5 5.5L31 7.5" fill="none" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
        {{-- the hammer (only visible while it swings) --}}
        <g class="tf-hammer">
            <path d="M15.5 19L30 4" stroke="#b45309" stroke-width="3" stroke-linecap="round" />
            <rect x="8" y="16.5" width="15" height="7" rx="1.8" fill="#334155" transform="rotate(45 15.5 20)" />
        </g>
        {{-- the anvil --}}
        <path fill="{{ $light ? '#ffffff' : '#4338ca' }}" d="M4 26h30c4 0 7.5-.8 10-2.5-1.2 3.6-4.6 6.5-9.5 6.5H32c-1.6 0-2.5 1-2.5 2.6V36h4v4H13v-4h4v-3.4c0-1.6-.9-2.6-2.5-2.6H8c-2.2 0-4-1.8-4-4z" />
    </svg>
    @if ($wordmark)
        <span class="font-display {{ $text }} font-extrabold tracking-tight leading-none">
            <span class="{{ $light ? 'text-white' : 'text-brand-700' }}">Test</span><span class="text-accent-500">Forge</span>
        </span>
    @endif
</span>
