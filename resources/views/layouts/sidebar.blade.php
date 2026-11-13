{{-- Admin/teacher sidebar. Expects $role and $navLinks. Off-canvas on mobile (sidebarOpen). --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
    <div class="flex items-center justify-between px-5 py-5">
        <a href="{{ route($role->homeRoute()) }}"><x-logo /></a>
        <button type="button" @click="sidebarOpen = false" class="rounded-md p-1 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Close menu">
            <x-icon name="close" />
        </button>
    </div>

    <div class="px-5">
        <span @class([
            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide',
            'bg-rose-100 text-rose-700' => $role === \App\Enums\UserRole::Admin,
            'bg-brand-100 text-brand-700' => $role === \App\Enums\UserRole::Teacher,
        ])>{{ $role->label() }} panel</span>
    </div>

    <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-3" aria-label="Main">
        @foreach ($navLinks as $link)
            @php($active = request()->routeIs($link['active']))
            <a href="{{ route($link['route']) }}" @if ($active) aria-current="page" @endif
               @class([
                   'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                   'bg-brand-50 text-brand-700' => $active,
                   'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
               ])>
                <x-icon :name="$link['icon'] ?? 'home'" :class="Arr::toCssClasses(['h-5 w-5', 'text-brand-600' => $active, 'text-gray-400 group-hover:text-gray-500' => ! $active])" />
                {{ __($link['label']) }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-gray-200 p-3">
        <div class="flex items-center gap-3 px-3 py-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ Str::upper(Str::substr(Auth::user()->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                <div class="truncate text-xs text-gray-500">{{ Auth::user()->email }}</div>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"><x-icon name="user" class="h-5 w-5 text-gray-400" /> Profile</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"><x-icon name="logout" class="h-5 w-5 text-gray-400" /> Log out</button>
        </form>
    </div>
</aside>
