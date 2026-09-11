@php($cover = $memory->cover)

@if ($cover)
    <a href="{{ route('memories.show', [$memorial, $memory]) }}" class="memory-card fade-in">
        <div class="memory-card__media">
            @if ($cover->is_video)
                <div class="memory-card__video">
                    <video src="{{ $cover->url }}" preload="metadata" muted playsinline
                           @if ($cover->poster_url) poster="{{ $cover->poster_url }}" @endif></video>
                    <span class="media-play" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>
                    </span>
                </div>
            @else
                <img src="{{ $cover->thumb_url }}" alt="" loading="lazy" width="{{ $cover->width }}" height="{{ $cover->height }}">
            @endif

            <div class="memory-card__overlay">
                <p class="memory-card__text">&rdquo;{{ $memory->excerpt(18) }}&ldquo;</p>
                <p class="memory-card__name">{{ $memory->author_name }}</p>
            </div>
        </div>
    </a>
@else
    <a href="{{ route('memories.show', [$memorial, $memory]) }}" class="memory-card memory-card--text fade-in">
        <span class="memory-card__quote" aria-hidden="true">&rdquo;</span>
        <p class="memory-card__text">{{ $memory->excerpt(28) }}</p>
        <p class="memory-card__name" style="margin-block-start: 14px;">{{ $memory->author_name }}</p>
    </a>
@endif
