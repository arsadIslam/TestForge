{{-- Shared by create and edit. Expects $series. --}}
<div>
    <x-input-label for="name" value="Name (e.g. SSC, WBSC)" />
    <x-text-input id="name" name="name" class="mt-1 block w-full" maxlength="100" required :value="old('name', $series->name)" />
    <x-input-error class="mt-2" :messages="array_merge($errors->get('name'), $errors->get('slug'))" />
</div>
<div>
    <x-input-label for="description" value="Description (shown to students)" />
    <textarea id="description" name="description" rows="3" maxlength="2000"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $series->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="price" value="Price (₹)" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="1" class="mt-1 block w-full" required
                      :value="old('price', \App\Support\Money::toRupees($series->price_paise ?? 0))" />
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
        <p class="mt-1 text-xs text-gray-500">Changing the price never affects orders already placed.</p>
    </div>
    <div>
        <x-input-label for="validity_days" value="Access lasts (days)" />
        <x-text-input id="validity_days" name="validity_days" type="number" min="1" max="3650" class="mt-1 block w-full"
                      :value="old('validity_days', $series->validity_days)" placeholder="Blank = lifetime" />
        <x-input-error class="mt-2" :messages="$errors->get('validity_days')" />
    </div>
</div>
<label class="inline-flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $series->is_active)) class="rounded border-gray-300 text-indigo-600">
    <span class="text-sm text-gray-700">On sale (inactive series can't be bought; existing buyers keep access)</span>
</label>
