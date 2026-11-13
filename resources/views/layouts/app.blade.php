{{-- The signed-in shell. $role and $navLinks come from App\View\Components\RoleLayout.
     Admin & teacher: sidebar. Student: top navigation. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('layouts._head', ['title' => $role->label()])
    </head>
    <body class="h-full bg-gray-50 font-sans text-gray-900 antialiased">
        @if ($role === \App\Enums\UserRole::Student)
            <div class="min-h-full">
                @include('layouts.navigation')
                @include('layouts._page')
            </div>
        @else
            <div x-data="{ sidebarOpen: false }" class="min-h-full lg:flex">
                @include('layouts.sidebar')

                <div class="min-w-0 flex-1">
                    {{-- mobile top bar --}}
                    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
                        <a href="{{ route($role->homeRoute()) }}"><x-logo size="sm" /></a>
                        <button type="button" @click="sidebarOpen = true" class="rounded-md p-2 text-gray-600 hover:bg-gray-100" aria-label="Open menu">
                            <x-icon name="menu" class="h-6 w-6" />
                        </button>
                    </div>

                    @include('layouts._page')
                </div>
            </div>
        @endif
    </body>
</html>
