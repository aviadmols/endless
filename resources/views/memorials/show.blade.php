@extends('layouts.app')

@section('title', $memorial->full_name . ' | ' . $memorial->display_subtitle)
@section('main-class', 'page-body--flush')

@push('meta')
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta property="og:title" content="{{ $memorial->full_name }}">
    <meta property="og:description" content="{{ $memorial->display_subtitle }}">
    @if ($memorial->cover_image_url)
        <meta property="og:image" content="{{ $memorial->cover_image_url }}">
    @endif
@endpush

@section('content')

{{-- ------------------------------------------------------------------ hero --}}
<section class="memorial-hero">
    <div class="memorial-hero__media" aria-hidden="true">
        @if ($memorial->hero_video_url)
            <video src="{{ $memorial->hero_video_url }}" autoplay muted loop playsinline></video>
        @elseif ($memorial->hero_image_url)
            <img src="{{ $memorial->hero_image_url }}" alt="">
        @endif
    </div>
    <div class="memorial-hero__overlay" aria-hidden="true"></div>

    <div class="memorial-hero__inner">
        @if ($memorial->religion_icon_url)
            <img src="{{ $memorial->religion_icon_url }}" alt="" class="memorial-hero__icon">
        @elseif ($memorial->religion?->value !== 'none')
            <div class="memorial-hero__icon"><x-religion-icon :religion="$memorial->religion" /></div>
        @endif

        <p class="memorial-hero__eyebrow">{{ $memorial->display_subtitle }}</p>
        <h1 class="memorial-hero__name">{{ $memorial->full_name }}</h1>

        @if ($memorial->dates_display)
            <p class="memorial-hero__dates">{{ $memorial->dates_display }}</p>
        @endif
        @if ($memorial->hebrew_dates)
            <p class="memorial-hero__dates" style="font-size: 14px; opacity: .75;">{{ $memorial->hebrew_dates }}</p>
        @endif

        @if ($memorial->portrait_video_url || $memorial->portrait_url)
            <div class="memorial-hero__portrait">
                @if ($memorial->portrait_video_url && ! $memorial->video_url)
                    <video src="{{ $memorial->portrait_video_url }}" autoplay muted loop playsinline controlslist="nodownload"></video>
                @elseif ($memorial->portrait_url)
                    <img src="{{ $memorial->portrait_url }}" alt="{{ $memorial->full_name }}">
                @endif
            </div>
        @endif
    </div>

    <svg class="memorial-hero__scroll" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="6 9 12 15 18 9"></polyline>
    </svg>
</section>

{{-- ------------------------------------------------------------ biography --}}
@if ($memorial->biography_title || $memorial->biography)
    <section class="bio">
        <div class="wrap wrap--text fade-in">
            @if ($memorial->biography_title)
                <h2 class="bio__title">{{ $memorial->biography_title }}</h2>
            @endif
            @if ($memorial->biography)
                <div class="bio__text rich">{!! $memorial->biography !!}</div>
            @endif
        </div>
    </section>
@endif

{{-- ------------------------------------------------------------- memories --}}
<section class="memories-section grad-down" id="memories">
    <div class="wrap wrap--edge">
        <h2 class="section-title">{{ $memorial->gender->memoriesHeading() }}</h2>

        @if ($memories->total() > 0)
            <div
                x-data="feed({ endpoint: '{{ route('memorials.feed', $memorial) }}', page: 2, hasMore: {{ $memories->hasMorePages() ? 'true' : 'false' }} })"
            >
                <div class="masonry" data-masonry data-cols-sm="1" data-cols-md="2" data-cols-lg="3" x-ref="list">
                    @foreach ($memories as $memory)
                        @include('memories._feed_item', ['memory' => $memory, 'memorial' => $memorial])
                    @endforeach
                </div>

                <div class="feed-actions">
                    <template x-if="hasMore">
                        <button type="button" class="btn btn--ghost" @click="loadMore()" :disabled="loading">
                            <span x-show="!loading">טעינת זיכרונות נוספים</span>
                            <span x-show="loading" x-cloak>טוען…</span>
                        </button>
                    </template>
                    <a href="{{ $memorial->share_url }}" class="btn">הוסיפו זיכרון</a>
                </div>

                <p class="center micro" style="color: var(--danger); margin-block-start: 12px;" x-show="error" x-text="error" x-cloak></p>
            </div>
        @else
            <div class="empty">
                <h3>עדיין אין כאן זיכרונות</h3>
                <p style="margin-block-end: 22px;">היו הראשונים לשתף רגע, סיפור או תמונה.</p>
                <a href="{{ $memorial->share_url }}" class="btn">הוסיפו את הזיכרון הראשון</a>
            </div>
        @endif
    </div>
</section>

{{-- -------------------------------------------------------------- gallery --}}
@if ($memorial->images->isNotEmpty())
    <section class="grad-up" style="padding-block: var(--section-y);" id="gallery"
             x-data="lightbox({{ Js::from($memorial->images->map(fn ($i) => ['url' => $i->url, 'alt' => $i->alt ?? ''])->values()) }})"
             @keydown.window="onKey($event)">
        <div class="wrap wrap--edge">
            <h2 class="section-title">רגעים של אהבה</h2>

            <div class="gallery" data-masonry data-cols-sm="2" data-cols-md="3" data-cols-lg="4">
                @foreach ($memorial->images as $index => $image)
                    <button type="button" @click="show({{ $index }})" class="fade-in" aria-label="הגדלת תמונה {{ $index + 1 }}">
                        <img src="{{ $image->thumb_url }}" alt="{{ $image->alt ?? '' }}" loading="lazy"
                             width="{{ $image->width }}" height="{{ $image->height }}">
                    </button>
                @endforeach
            </div>
        </div>

        <div class="lightbox" x-show="open" x-cloak @click.self="close()" role="dialog" aria-modal="true">
            <button type="button" class="lightbox__close" @click="close()" aria-label="סגירה">&times;</button>
            <button type="button" class="lightbox__nav lightbox__nav--prev" @click="prev()" aria-label="הקודם">‹</button>
            <img :src="images[index]?.url" :alt="images[index]?.alt">
            <button type="button" class="lightbox__nav lightbox__nav--next" @click="next()" aria-label="הבא">›</button>
        </div>
    </section>
@endif

{{-- ---------------------------------------------------------------- quote --}}
@if ($memorial->quote)
    <section class="quote-section">
        <div class="wrap fade-in">
            <p class="quote-section__text">{{ $memorial->quote }}</p>
            @if ($memorial->quote_name)
                <p class="quote-section__name">{{ $memorial->quote_name }}</p>
            @endif
        </div>
    </section>
@endif

{{-- -------------------------------------------------------------- founder --}}
@if ($memorial->founder_display)
    <section class="founder">
        <p>{{ $memorial->founder_display }}</p>
    </section>
@endif

{{-- ----------------------------------------------------------- sticky bar --}}
<div class="sticky-bar">
    <a href="{{ $memorial->share_url }}" class="btn">הוסיפו זיכרון</a>
    <button type="button" class="btn btn--ghost" style="background: var(--paper);"
            x-data="{ share() {
                const data = { title: '{{ $memorial->full_name }}', text: '{{ $memorial->display_subtitle }}', url: '{{ $memorial->url }}' };
                if (navigator.share) { navigator.share(data); } else { navigator.clipboard.writeText(data.url); this.$el.textContent = 'הקישור הועתק'; }
            } }" @click="share()">שיתוף</button>
</div>

@if ($isOwner)
    <div class="wrap" style="padding-block: 30px;">
        <div class="alert alert--info center">
            אתם הבעלים של העמוד.
            <a href="{{ route('dashboard.memorial.edit') }}" class="link-underline">עריכת העמוד</a>
            ·
            <a href="{{ route('dashboard.memories.index') }}" class="link-underline">ניהול זיכרונות</a>
        </div>
    </div>
@endif
@endsection
