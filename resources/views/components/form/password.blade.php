@props([
    'id',
    'label' => 'Contraseña',
    'placeholder' => 'Tu contraseña',
    'toggleLabel' => null,
    'liveFeedback' => false,
])

@php
    $toggleLabel = $toggleLabel ?? mb_strtolower($label);
@endphp

<x-form.input
    :id="$id"
    :label="$label"
    type="password"
    icon="lock"
    :placeholder="$placeholder"
    autocomplete="new-password"
    :live-feedback="$liveFeedback"
    {{ $attributes }}
>
    <button
        type="button"
        aria-label="Mostrar {{ $toggleLabel }}"
        aria-controls="{{ $id }}"
        aria-pressed="false"
        class="absolute inset-y-0 right-1.5 my-auto flex size-11 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:text-gold-light focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-gold motion-reduce:transition-none"
        data-password-toggle
        data-show-label="Mostrar {{ $toggleLabel }}"
        data-hide-label="Ocultar {{ $toggleLabel }}"
        hidden
    >
        <span data-password-show><x-ui.icon name="eye" /></span>
        <span data-password-hide hidden><x-ui.icon name="eye-off" /></span>
    </button>
</x-form.input>
