{{-- Shows session('success') / session('error') set by controllers via ->with('success', '...'). --}}
@foreach (['success' => 'border-green-200 bg-green-50 text-green-800', 'error' => 'border-red-200 bg-red-50 text-red-800'] as $type => $classes)
    @if (session($type))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div x-data="{ show: true }" x-show="show" role="alert"
                 class="flex items-start justify-between rounded-md border px-4 py-3 text-sm {{ $classes }}">
                <span>{{ session($type) }}</span>
                <button type="button" @click="show = false" class="ms-4 font-semibold opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
            </div>
        </div>
    @endif
@endforeach
