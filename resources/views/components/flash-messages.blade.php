{{-- Shows session('success') / session('error') set by controllers via ->with('success', '...'). --}}
@foreach (['success' => ['border-green-200 bg-green-50 text-green-800', 'check'], 'error' => ['border-red-200 bg-red-50 text-red-800', 'bolt']] as $type => [$classes, $icon])
    @if (session($type))
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <div x-data="{ show: true }" x-show="show" x-transition role="{{ $type === 'error' ? 'alert' : 'status' }}"
                 class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $classes }}">
                <x-icon :name="$icon" class="mt-0.5 h-5 w-5" />
                <span class="flex-1">{{ session($type) }}</span>
                <button type="button" @click="show = false" class="font-semibold opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
            </div>
        </div>
    @endif
@endforeach
