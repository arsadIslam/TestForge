{{-- Shared by create and edit. Expects $test. --}}
<div>
    <x-input-label for="title" value="Title" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" maxlength="200" required autofocus
                  :value="old('title', $test->title)" />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="test_series_id" value="Series" />
    <x-select-input id="test_series_id" name="test_series_id" class="mt-1 block w-full sm:w-80">
        <option value="">Free — anyone can take it</option>
        @foreach ($seriesOptions as $id => $name)
            <option value="{{ $id }}" @selected((int) old('test_series_id', $test->test_series_id) === $id)>{{ $name }} (paid)</option>
        @endforeach
    </x-select-input>
    <x-input-error class="mt-2" :messages="$errors->get('test_series_id')" />
    <p class="mt-1 text-xs text-gray-500">Tests in a paid series can only be started by students who bought it or have an All-Access pass.</p>
</div>

<div>
    <x-input-label for="description" value="Description (optional)" />
    <textarea id="description" name="description" rows="3" maxlength="5000"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $test->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
    <div>
        <x-input-label for="duration_minutes" value="Duration (minutes)" />
        <x-text-input id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" class="mt-1 block w-full" required
                      :value="old('duration_minutes', $test->duration_minutes)" />
        <x-input-error class="mt-2" :messages="$errors->get('duration_minutes')" />
    </div>
    <div>
        <x-input-label for="pass_marks" value="Pass marks" />
        <x-text-input id="pass_marks" name="pass_marks" type="number" step="0.25" min="0" class="mt-1 block w-full" required
                      :value="old('pass_marks', $test->pass_marks)" />
        <x-input-error class="mt-2" :messages="$errors->get('pass_marks')" />
        @if ($test->exists)
            <p class="mt-1 text-xs text-gray-500">Total marks right now: {{ $test->total_marks }}</p>
        @endif
    </div>
    <div>
        <x-input-label for="max_attempts" value="Attempts allowed" />
        <x-text-input id="max_attempts" name="max_attempts" type="number" min="1" max="10" class="mt-1 block w-full" required
                      :value="old('max_attempts', $test->max_attempts)" />
        <x-input-error class="mt-2" :messages="$errors->get('max_attempts')" />
    </div>
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="starts_at" value="Opens at (optional)" />
        <x-text-input id="starts_at" name="starts_at" type="datetime-local" class="mt-1 block w-full"
                      :value="old('starts_at', $test->starts_at?->format('Y-m-d\TH:i'))" />
        <x-input-error class="mt-2" :messages="$errors->get('starts_at')" />
    </div>
    <div>
        <x-input-label for="ends_at" value="Closes at (optional)" />
        <x-text-input id="ends_at" name="ends_at" type="datetime-local" class="mt-1 block w-full"
                      :value="old('ends_at', $test->ends_at?->format('Y-m-d\TH:i'))" />
        <x-input-error class="mt-2" :messages="$errors->get('ends_at')" />
    </div>
</div>
<p class="-mt-4 text-xs text-gray-500">Times are in {{ config('app.timezone') }}. Leave empty for no limit.</p>

<label class="inline-flex items-center gap-2">
    <input type="hidden" name="shuffle_questions" value="0">
    <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $test->shuffle_questions))
           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
    <span class="text-sm text-gray-700">Shuffle question order for each student</span>
</label>
