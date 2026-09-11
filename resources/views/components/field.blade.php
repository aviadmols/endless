@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'placeholder' => ' ',
    'always' => false,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $value = old($name, $value);
    $filled = filled($value) || $always || in_array($type, ['date', 'time', 'datetime-local'], true);
@endphp

<div @class(['field', 'field--always' => $filled, 'has-error' => $hasError])>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        @required($required)
        {{ $attributes->except(['id']) }}
    >
    <label for="{{ $id }}">{{ $label }}@if ($required)*@endif</label>
    @if ($hint)
        <small>{{ $hint }}</small>
    @endif
    @error($name)
        <span class="error">{{ $message }}</span>
    @enderror
</div>
