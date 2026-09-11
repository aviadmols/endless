@extends('layouts.app')

@section('title', 'זיכרון מאת ' . $memory->author_name . ' | ' . $memorial->full_name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="memory-page"
     @if ($previous || $next)
        x-data
        @keydown.window.arrow-left="$refs.next?.click()"
        @keydown.window.arrow-right="$refs.previous?.click()"
     @endif>

    <div class="wrap wrap--read" style="padding-block-start: 0;">
        <article class="memory-paper fade-in">

            @php($cover = $memory->media->first())
            @if ($cover)
                <div class="memory-paper__cover">
                    @if ($cover->is_video)
                        <video src="{{ $cover->url }}" controls playsinline preload="metadata"
                               @if ($cover->poster_url) poster="{{ $cover->poster_url }}" @endif></video>
                    @else
                        <img src="{{ $cover->url }}" alt="" width="{{ $cover->width }}" height="{{ $cover->height }}">
                    @endif
                </div>
            @endif

            <div class="memory-paper__meta">
                <span>מאת: {{ $memory->author_name }}</span>
                <a href="{{ route('memorials.show', $memorial) }}" class="link-underline">חזרה לפרופיל</a>
            </div>

            @if (! $memory->is_approved)
                <div style="padding: 16px 26px 0;">
                    <div class="alert alert--info">הזיכרון הזה ממתין לאישור ואינו מוצג בעמוד הציבורי.</div>
                </div>
            @endif

            <div class="memory-paper__body rich">
                @if ($memory->title)
                    <h2 style="font-size: 22px; font-weight: var(--fw-medium); margin-block-end: 12px;">{{ $memory->title }}</h2>
                @endif
                {!! $memory->body !!}
            </div>

            @if ($memory->media->count() > 1)
                <div class="memory-paper__images">
                    @foreach ($memory->media->slice(1) as $media)
                        @if ($media->is_video)
                            <video src="{{ $media->url }}" controls playsinline preload="metadata"
                                   @if ($media->poster_url) poster="{{ $media->poster_url }}" @endif></video>
                        @else
                            <img src="{{ $media->url }}" alt="" loading="lazy" width="{{ $media->width }}" height="{{ $media->height }}">
                        @endif
                    @endforeach
                </div>
            @endif

            <p class="micro center" style="padding: 22px 26px 30px;">
                {{ $memory->date_display }}
                @if ($position)
                    <span aria-hidden="true"> · </span>זיכרון {{ $position }} מתוך {{ $total }}
                @endif
            </p>
        </article>

        {{-- ---------------------------------------------------- prev / next --}}
        @if ($previous || $next)
            <nav class="memory-nav fade-in" aria-label="מעבר בין זיכרונות">
                @if ($previous)
                    <a href="{{ route('memories.show', [$memorial, $previous]) }}" class="memory-nav__link" x-ref="previous" rel="prev">
                        <span class="memory-nav__arrow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                        @if ($previous->cover && ! $previous->cover->is_video)
                            <img class="memory-nav__thumb" src="{{ $previous->cover->thumb_url }}" alt="" loading="lazy">
                        @endif
                        <span class="memory-nav__text">
                            <span class="memory-nav__label">הזיכרון הקודם</span>
                            <span class="memory-nav__name">{{ $previous->author_name }}</span>
                        </span>
                    </a>
                @else
                    <span class="memory-nav__link is-empty" aria-hidden="true"></span>
                @endif

                @if ($next)
                    <a href="{{ route('memories.show', [$memorial, $next]) }}" class="memory-nav__link memory-nav__link--next" x-ref="next" rel="next">
                        <span class="memory-nav__text">
                            <span class="memory-nav__label">הזיכרון הבא</span>
                            <span class="memory-nav__name">{{ $next->author_name }}</span>
                        </span>
                        @if ($next->cover && ! $next->cover->is_video)
                            <img class="memory-nav__thumb" src="{{ $next->cover->thumb_url }}" alt="" loading="lazy">
                        @endif
                        <span class="memory-nav__arrow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </span>
                    </a>
                @else
                    <span class="memory-nav__link is-empty" aria-hidden="true"></span>
                @endif
            </nav>
        @endif
    </div>
</div>

@if ($others->isNotEmpty())
    <section class="memories-section grad-down">
        <div class="wrap wrap--edge">
            <h2 class="section-title">{{ $memorial->gender->memoriesHeading() }}</h2>
            <div class="masonry" data-masonry data-cols-sm="1" data-cols-md="2" data-cols-lg="3">
                @foreach ($others as $other)
                    @include('memories._feed_item', ['memory' => $other, 'memorial' => $memorial])
                @endforeach
            </div>
            <div class="feed-actions">
                <a href="{{ $memorial->share_url }}" class="btn">הוסיפו זיכרון</a>
            </div>
        </div>
    </section>
@endif
@endsection
