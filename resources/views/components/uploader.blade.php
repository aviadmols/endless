@props([
    'name' => 'media',
    'max' => 10,
    'title' => 'העלאת תמונות או סרטון',
    'hint' => null,
    'videos' => true,
])

@php
    // Explicit types rather than image/* on purpose: iOS converts HEIC photos to JPEG
    // when the accept list does not mention HEIC, which is what the server can read.
    $accept = $videos
        ? 'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime'
        : 'image/jpeg,image/png,image/webp,image/gif';

    $hint ??= $videos
        ? 'תמונות JPG, PNG, WebP, GIF עד 8MB · סרטונים MP4, WebM, MOV עד 60MB'
        : 'JPG, PNG, WebP, GIF · עד 8MB לתמונה';

    $id = 'upload-' . \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::random(5);
@endphp

<div
    x-data="uploader({ max: {{ (int) $max }}, videos: {{ $videos ? 'true' : 'false' }} })"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="onDrop($event)"
>
    {{-- The label opens the picker natively; no scripted click, so the phone's
         photo picker is never re-opened mid-selection. --}}
    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}[]"
        accept="{{ $accept }}"
        multiple
        x-ref="input"
        class="upload__input"
        @change="add($event.target.files)"
        {{ $attributes }}
    >

    <label class="upload" for="{{ $id }}" :class="dragging && 'is-dragging'">
        <span class="upload__icon" aria-hidden="true">
            <img src="{{ asset('images/brand/add-image.svg') }}" alt="">
        </span>
        <span class="upload__title">{{ $title }}</span>
        <span class="upload__hint">{{ $hint }}</span>
        <span class="upload__count" x-show="files.length" x-cloak x-text="files.length + ' קבצים נבחרו'"></span>
    </label>

    <p class="upload__error" x-show="error" x-cloak x-text="error"></p>

    <div class="previews" x-show="previews.length" x-cloak>
        <template x-for="(preview, index) in previews" :key="preview.key">
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
        <p class="upload__error">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="upload__error">{{ $message }}</p>
    @enderror
</div>
