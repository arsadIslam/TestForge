{{-- Shared by create and edit. Expects $plan. --}}
<div>
    <x-input-label for="name" value="Name (e.g. All Access · 90 days)" />
    <x-text-input id="name" name="name" class="mt-1 block w-full" maxlength="100" required :value="old('name', $plan->name)" />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="price" value="Price (₹)" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="1" class="mt-1 block w-full" required
                      :value="old('price', \App\Support\Money::toRupees($plan->price_paise ?? 0))" />
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
    </div>
    <div>
        <x-input-label for="duration_days" value="Unlocks every test for (days)" />
        <x-text-input id="duration_days" name="duration_days" type="number" min="1" max="3650" class="mt-1 block w-full" required
                      :value="old('duration_days', $plan->duration_days)" />
        <x-input-error class="mt-2" :messages="$errors->get('duration_days')" />
    </div>
</div>
<label class="inline-flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active)) class="rounded border-gray-300 text-indigo-600">
    <span class="text-sm text-gray-700">On sale</span>
</label>
