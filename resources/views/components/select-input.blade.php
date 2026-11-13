@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500']) }}>
    {{ $slot }}
</select>
