@props([
    'name' => 'media',
    'max' => 10,
    'title' => 'העלאת תמונות או סרטון',
    'hint' => null,
    'accept' => 'image/*,video/mp4,video/webm,video/quicktime',
    'videos' => true,
])

@php
    $hint ??= $videos
        ? 'תמונות JPG, PNG, WebP, GIF עד 8MB · סרטונים MP4, WebM, MOV עד 60MB · אפשר לבחור כמה קבצים'
        : 'JPG, PNG, WebP, GIF · עד 8MB לתמונה · אפשר לבחור כמה תמונות';
@endphp

<div
    x-data="uploader({ max: {{ (int) $max }}, videos: {{ $videos ? 'true' : 'false' }} })"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="onDrop($event)"
>
    <div class="upload" :class="dragging && 'is-dragging'" @click="pick()" role="button" tabindex="0" @keydown.enter.prevent="pick()" @keydown.space.prevent="pick()">
        <div class="upload__icon" aria-hidden="true">
            <img src="{{ asset('images/brand/add-image.svg') }}" alt="">
        </div>
        <p class="upload__title">{{ $title }}</p>
        <p class="upload__hint">{{ $hint }}</p>
        <input type="file" name="{{ $name }}[]" accept="{{ $accept }}" multiple x-ref="input" class="sr-only" {{ $attributes }}>
    </div>

    <p x-show="error" x-text="error" x-cloak style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;"></p>

    <div class="previews" x-show="previews.length" x-cloak>
        <template x-for="(preview, index) in previews" :key="preview.url">
            <div class="preview">
                <template x-if="preview.isVideo">
                    <video :src="preview.url" muted playsinline preload="metadata"></video>
                </template>
                <template x-if="!preview.isVideo">
                    <img :src="preview.url" :alt="preview.name">
                </template>
                <span class="media-play media-play--sm" x-show="preview.isVideo" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>
                </span>
                <button type="button" @click="remove(index)" :aria-label="'הסרת ' + preview.name">&times;</button>
            </div>
        </template>
    </div>

    @error($name)
        <p style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p style="color: var(--danger); font-size: var(--fs-micro); margin-block-start: 8px;">{{ $message }}</p>
    @enderror
</div>
