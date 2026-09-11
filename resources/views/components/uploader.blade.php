@props([
    'name' => 'images',
    'max' => 10,
    'title' => 'העלאת תמונות',
    'hint' => 'JPG, PNG, WebP, GIF · עד 8MB לתמונה · אפשר לבחור כמה תמונות',
])

<div
    x-data="uploader({ max: {{ (int) $max }} })"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="onDrop($event)"
>
    <div class="upload" :class="dragging && 'is-dragging'" @click="pick()" role="button" tabindex="0" @keydown.enter.prevent="pick()" @keydown.space.prevent="pick()">
        <div class="upload__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="14" height="14" rx="2"></rect>
                <circle cx="8" cy="9" r="1.6"></circle>
                <path d="M3 15l4-4 5 5"></path>
                <path d="M19 8v8M23 12h-8" transform="translate(-2 0)"></path>
            </svg>
        </div>
        <p class="upload__title">{{ $title }}</p>
        <p class="upload__hint">{{ $hint }}</p>
        <input type="file" name="{{ $name }}[]" accept="image/*" multiple x-ref="input" class="sr-only" {{ $attributes }}>
    </div>

    <p class="error" x-show="error" x-text="error" x-cloak style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;"></p>

    <div class="previews" x-show="previews.length" x-cloak>
        <template x-for="(preview, index) in previews" :key="preview.url">
            <div class="preview">
                <img :src="preview.url" :alt="preview.name">
                <button type="button" @click="remove(index)" :aria-label="'הסרת ' + preview.name">&times;</button>
            </div>
        </template>
    </div>

    @error($name)
        <p class="error" style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="error" style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;">{{ $message }}</p>
    @enderror
</div>
