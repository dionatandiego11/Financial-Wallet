@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-black dark:text-gray-200']) }}>
    {{-- Alterado para text-black para preto puro no modo claro --}}
    {{ $value ?? $slot }}
</label>
