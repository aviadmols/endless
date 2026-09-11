@props([
    'name' => 'body',
    'value' => null,
    'placeholder' => 'כתבו כאן…',
    'label' => null,
])

@php($id = 'editor-' . \Illuminate\Support\Str::slug($name))

<div>
    @if ($label)
        <label for="{{ $id }}" style="display:block; font-size: var(--fs-small); font-weight: var(--fw-medium); margin-block-end: 8px;">{{ $label }}</label>
    @endif

    <textarea name="{{ $name }}" id="{{ $id }}" class="sr-only" aria-hidden="true" tabindex="-1">{{ old($name, $value) }}</textarea>
    <div class="editor-wrap" data-editor data-target="#{{ $id }}" data-placeholder="{{ $placeholder }}"></div>

    @error($name)
        <p class="error" style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;">{{ $message }}</p>
    @enderror
</div>

<noscript>
    <p class="micro">העורך העשיר דורש JavaScript. ניתן לכתוב טקסט רגיל בשדה שלמעלה.</p>
</noscript>
