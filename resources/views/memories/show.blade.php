@extends('layouts.app')

@section('title', 'זיכרון מאת ' . $memory->author_name . ' | ' . $memorial->full_name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="memory-page">
    <div class="wrap wrap--read" style="padding-block-start: 0;">
        <article class="memory-paper fade-in">
            @if ($memory->cover)
                <div class="memory-paper__cover">
                    <img src="{{ $memory->cover->url }}" alt="" width="{{ $memory->cover->width }}" height="{{ $memory->cover->height }}">
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

            @if ($memory->images->count() > 1)
                <div class="memory-paper__images">
                    @foreach ($memory->images->slice(1) as $image)
                        <img src="{{ $image->url }}" alt="" loading="lazy" width="{{ $image->width }}" height="{{ $image->height }}">
                    @endforeach
                </div>
            @endif

            <p class="micro center" style="padding: 20px 26px 0;">{{ $memory->date_display }}</p>

            <div class="memory-paper__actions">
                <a href="{{ route('memorials.show', $memorial) }}" class="btn btn--ghost" style="min-width: min(600px, 100%);">חזרה לפרופיל</a>
            </div>
        </article>
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
