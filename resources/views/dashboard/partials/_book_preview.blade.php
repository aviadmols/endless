{{-- The flip-through. Rendered on its own so the JS can swap it in when an option changes. --}}
<div class="book" data-book style="--book-ratio: {{ $size->ratio() }}; --type-scale: {{ $size->typeScale() }}">
    <div class="book__stage">
        @foreach ($pages as $i => $page)
            <article @class(['book__page', 'book__page--'.$page->type, 'is-current' => $i === 0])
                     data-book-page aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">

                @switch($page->type)
                    @case('cover')
                        @if ($page->images)
                            <div class="book__cover-media">
                                <img src="{{ $page->images[0]['url'] }}" alt="" loading="lazy">
                            </div>
                        @endif
                        <div class="book__cover-text">
                            @if ($page->eyebrow)<p class="book__eyebrow">{{ $page->eyebrow }}</p>@endif
                            <h3 class="book__name">{{ $page->title }}</h3>
                            @if ($page->caption)<p class="book__dates">{{ $page->caption }}</p>@endif
                        </div>
                        @break

                    @case('divider')
                        <div class="book__divider">
                            @if ($page->eyebrow)<p class="book__eyebrow">{{ $page->eyebrow }}</p>@endif
                            <h3>{{ $page->title }}</h3>
                        </div>
                        @break

                    @case('photos')
                        <div class="book__grid" style="--cols: {{ $page->columns }}">
                            @foreach ($page->images as $image)
                                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy">
                            @endforeach
                        </div>
                        @if ($page->caption)<p class="book__byline">{{ $page->caption }}</p>@endif
                        @break

                    @case('blank')
                        <span class="sr-only">עמוד ריק</span>
                        @break

                    @case('closing')
                        <div class="book__closing">
                            @if ($page->title)<blockquote>{{ $page->title }}</blockquote>@endif
                            @if ($page->caption)<p class="book__byline">{{ $page->caption }}</p>@endif
                        </div>
                        @break

                    @default
                        @if ($page->title)<h3 class="book__title">{{ $page->title }}</h3>@endif
                        @if ($page->images)
                            <div class="book__grid" style="--cols: {{ $page->columns }}">
                                @foreach ($page->images as $image)
                                    <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy">
                                @endforeach
                            </div>
                        @endif
                        @if ($page->body)<p class="book__body">{{ $page->body }}</p>@endif
                        @if ($page->caption)<p class="book__byline">{{ $page->caption }}</p>@endif
                @endswitch

                <span class="book__folio">{{ $i + 1 }}</span>
            </article>
        @endforeach
    </div>

    <div class="book__controls">
        <button type="button" class="btn btn--ghost btn--sm" data-book-prev aria-label="העמוד הקודם">→</button>
        <p class="book__counter" aria-live="polite">
            <span data-book-current>1</span> מתוך {{ count($pages) }}
        </p>
        <button type="button" class="btn btn--ghost btn--sm" data-book-next aria-label="העמוד הבא">←</button>
    </div>

    <input type="range" class="book__scrub" min="1" max="{{ max(1, count($pages)) }}" value="1"
           data-book-scrub aria-label="דפדוף בספר">
</div>
