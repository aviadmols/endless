@props([
    'name',
    'options',
    'selected' => null,
    'label' => 'סמל דת',
    'required' => false,
    'id' => null,
])

@php
    $id = $id ?? $name;
    $selected = array_key_exists($selected, $options) ? $selected : array_key_first($options);
@endphp

{{-- A native select carries the value; the JS in select-icon.js swaps in the
     button + listbox below it so each option can show its mark. --}}
<div @class(['field', 'field--always', 'icon-select', 'has-error' => $errors->has($name)]) data-icon-select>
    <select name="{{ $name }}" id="{{ $id }}" class="icon-select__native" @required($required)>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected($selected === $value)>{{ $text }}</option>
        @endforeach
    </select>

    <button type="button" class="icon-select__button" id="{{ $id }}-button" hidden
            aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $id }}-listbox">
        <span class="icon-select__mark" data-icon-slot><x-religion-icon :religion="$selected" /></span>
        <span class="icon-select__value">{{ $options[$selected] }}</span>
    </button>

    <ul class="icon-select__list" id="{{ $id }}-listbox" role="listbox" hidden
        aria-labelledby="{{ $id }}-label" tabindex="-1">
        @foreach ($options as $value => $text)
            <li class="icon-select__option" role="option" id="{{ $id }}-option-{{ $value }}"
                data-value="{{ $value }}" aria-selected="{{ $selected === $value ? 'true' : 'false' }}">
                <span class="icon-select__mark"><x-religion-icon :religion="$value" /></span>
                <span>{{ $text }}</span>
            </li>
        @endforeach
    </ul>

    <label for="{{ $id }}" id="{{ $id }}-label">{{ $label }}</label>
    @error($name)<span class="error">{{ $message }}</span>@enderror
</div>
