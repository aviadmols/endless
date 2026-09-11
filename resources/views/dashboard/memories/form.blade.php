@extends('layouts.dashboard')

@section('title', ($memory ? 'עריכת זיכרון' : 'הוספת זיכרון') . ' | ' . setting('general.site_name', 'Endless'))
@section('page-title', $memory ? 'עריכת זיכרון' : 'הוספת זיכרון')
@section('page-sub', $memory ? 'מאת ' . $memory->author_name : 'הזיכרון יפורסם מיד בעמוד')

@section('dashboard')

<div class="card">
    <div class="card__body">
        <form method="POST" action="{{ $memory ? route('dashboard.memories.update', $memory) : route('dashboard.memories.store') }}" enctype="multipart/form-data" class="form">
            @csrf
            @if ($memory) @method('PUT') @endif

            <div class="form-row form-row--2">
                <x-field name="author_name" label="שם המעלה" :value="$memory?->author_name ?? auth()->user()->name" required />
                <x-field name="title" label="כותרת (לא חובה)" :value="$memory?->title" />
            </div>

            <x-editor name="body" :value="$memory?->body" label="הזיכרון" placeholder="מה תרצו לספר?" />

            <div style="margin-block-start: 24px;">
                <h3 class="eyebrow" style="font-size: 13px; letter-spacing: 2px; margin-block-end: 14px;">תמונות</h3>

                @if ($memory && $memory->images->isNotEmpty())
                    <div class="previews" style="margin-block-end: 16px;">
                        @foreach ($memory->images as $image)
                            <div class="preview">
                                <img src="{{ $image->thumb_url }}" alt="">
                                <form method="POST" action="{{ route('dashboard.memories.images.destroy', [$memory, $image]) }}" onsubmit="return confirm('להסיר את התמונה?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" aria-label="הסרה">&times;</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <x-uploader name="images" :max="config('endless.uploads.memory_max_images')" title="הוספת תמונות" />
            </div>

            <div style="display: flex; gap: 12px; margin-block-start: 26px;">
                <button type="submit" class="btn">{{ $memory ? 'שמירת השינויים' : 'הוספת הזיכרון' }}</button>
                <a href="{{ route('dashboard.memories.index') }}" class="btn btn--ghost">ביטול</a>
            </div>
        </form>
    </div>
</div>
@endsection
