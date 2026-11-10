<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Users') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            {{-- Filters (GET, so the URL is shareable and pagination keeps them) --}}
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3 bg-white p-4 shadow-sm sm:rounded-lg">
                <div>
                    <x-input-label for="search" value="Search" />
                    <x-text-input id="search" name="search" type="search" class="mt-1 block w-64" placeholder="Name or email" :value="$filters['search']" />
                </div>
                <div>
                    <x-input-label for="role" value="Role" />
                    <x-select-input id="role" name="role" class="mt-1 block">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <x-primary-button>Filter</x-primary-button>
                @if ($filters['search'] !== '' || $filters['role'])
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                @endif
            </form>

            <x-input-error :messages="$errors->get('role')" />

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Joined</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    @can('update', $user)
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <x-select-input name="role" class="py-1 text-sm" aria-label="Role for {{ $user->name }}">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                                @endforeach
                                            </x-select-input>
                                            <x-secondary-button type="submit" class="!px-3 !py-1.5">Save</x-secondary-button>
                                        </form>
                                    @else
                                        {{ $user->role->label() }} <span class="text-xs text-gray-400">(you)</span>
                                    @endcan
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->is_active)
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('update', $user)
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}"
                                              onsubmit="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} {{ addslashes($user->name) }}?')">
                                            @csrf
                                            @method('PATCH')
                                            @if ($user->is_active)
                                                <x-danger-button class="!px-3 !py-1.5">Deactivate</x-danger-button>
                                            @else
                                                <x-primary-button class="!px-3 !py-1.5">Activate</x-primary-button>
                                            @endif
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No users match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-admin-layout>
