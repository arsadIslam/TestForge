<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Admin Dashboard') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Users</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <x-stat-card :label="str($role->label())->plural()" :value="$usersByRole[$role->value] ?? 0" />
                    @endforeach
                </div>
            </section>

            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Content</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-stat-card label="Subjects" :value="$counts['subjects']" />
                    <x-stat-card label="Topics" :value="$counts['topics']" />
                    <x-stat-card label="Questions" :value="$counts['questions']" />
                    <x-stat-card label="Attempts" :value="$counts['attempts']" />
                </div>
            </section>

            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Tests</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach (\App\Enums\TestStatus::cases() as $status)
                        <x-stat-card :label="$status->label()" :value="$testsByStatus[$status->value] ?? 0" />
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
