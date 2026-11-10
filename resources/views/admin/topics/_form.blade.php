{{-- Shared by create and edit. Expects $topic and $subjects (id => name). --}}
<div>
    <x-input-label for="subject_id" value="Subject" />
    <x-select-input id="subject_id" name="subject_id" class="mt-1 block w-full" required>
        <option value="">Choose a subject…</option>
        @foreach ($subjects as $id => $name)
            <option value="{{ $id }}" @selected((int) old('subject_id', $topic->subject_id) === $id)>{{ $name }}</option>
        @endforeach
    </x-select-input>
    <x-input-error class="mt-2" :messages="$errors->get('subject_id')" />
</div>

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" maxlength="120" required
                  :value="old('name', $topic->name)" />
    <x-input-error class="mt-2" :messages="array_merge($errors->get('name'), $errors->get('slug'))" />
    <p class="mt-1 text-xs text-gray-500">Topic names must be unique within their subject.</p>
</div>
