@php($cover = $memory->cover)

@if ($cover)
    <a href="{{ route('memories.show', [$memorial, $memory]) }}" class="memory-card fade-in">
        <div class="memory-card__media">
            <img src="{{ $cover->thumb_url }}" alt="" loading="lazy" width="{{ $cover->width }}" height="{{ $cover->height }}">
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
