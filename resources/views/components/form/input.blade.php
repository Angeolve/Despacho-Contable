@props(['id', 'label', 'type' => 'text', 'icon', 'error' => null, 'success' => null, 'liveFeedback' => false])

@php
    $hasError = filled($error) || in_array($attributes->get('aria-invalid'), [true, 'true'], true);
    $hasSuccess = ! $hasError && filled($success);
    $feedback = filled($error) ? $error : ($hasSuccess ? $success : null);
    $feedbackId = $id.'-feedback';
    $describedBy = trim(($attributes->get('aria-describedby') ?? '').((filled($feedback) || $liveFeedback) ? ' '.$feedbackId : ''));
@endphp

<div class="space-y-2.5">
    <label for="{{ $id }}" class="block text-xs font-medium tracking-wide text-ivory/90">
        {{ $label }}
    </label>
    <div class="relative">
        <x-ui.icon :name="$icon" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-gold/70" />
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if ($hasSuccess) data-valid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except(['aria-invalid', 'aria-describedby', 'data-valid'])->class([
                'login-input block h-13 w-full rounded-lg border bg-black/15 pl-12 text-sm text-ivory outline-none transition-colors motion-reduce:transition-none',
                'pr-4' => $slot->isEmpty(),
                'pr-14' => ! $slot->isEmpty(),
            ]) }}
        >
        {{ $slot }}
    </div>

    @if (filled($feedback) || $liveFeedback)
        <p
            id="{{ $feedbackId }}"
            role="{{ $hasError && ! $liveFeedback ? 'alert' : 'status' }}"
            aria-atomic="true"
            @if ($liveFeedback) aria-live="polite" data-field-feedback @endif
            @if (! filled($feedback)) hidden @endif
            @class(['text-xs leading-relaxed break-words', 'text-rose-200' => $hasError, 'text-gold-light' => ! $hasError])
        >{{ $feedback }}</p>
    @endif
</div>
