{{-- Student top navigation. Expects $role and $navLinks (from RoleLayout). --}}
<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route($role->homeRoute()) }}"><x-logo /></a>

                <div class="hidden h-full gap-1 sm:flex">
                    @foreach ($navLinks as $link)
                        <x-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                            {{ __($link['label']) }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full py-1 pe-2 ps-1 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">{{ Str::upper(Str::substr(Auth::user()->name, 0, 1)) }}</span>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current text-gray-400" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="rounded-md p-2 text-gray-500 hover:bg-gray-100" aria-label="Menu">
                    <x-icon name="menu" class="h-6 w-6" x-show="! open" />
                    <x-icon name="close" class="h-6 w-6" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak class="border-t border-gray-200 sm:hidden">
        <div class="space-y-1 py-2">
            @foreach ($navLinks as $link)
                <x-responsive-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                    {{ __($link['label']) }}
                </x-responsive-nav-link>
            @endforeach
        </div>
        <div class="border-t border-gray-200 py-3">
            <div class="px-4 text-sm font-medium text-gray-800">{{ Auth::user()->name }}</div>
            <div class="px-4 text-xs text-gray-500">{{ Auth::user()->email }}</div>
            <div class="mt-2 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
