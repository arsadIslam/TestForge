{{-- Shared by create and edit. Expects $subject (a new or existing Subject). --}}
<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" maxlength="100" required autofocus
                  :value="old('name', $subject->name)" />
    {{-- slug errors are shown here too: the slug is generated from the name --}}
    <x-input-error class="mt-2" :messages="array_merge($errors->get('name'), $errors->get('slug'))" />
    <p class="mt-1 text-xs text-gray-500">The URL slug is generated from the name automatically.</p>
</div>
