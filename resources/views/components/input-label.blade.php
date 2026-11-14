@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-[#39362f]']) }}>
    {{ $value ?? $slot }}
</label>
