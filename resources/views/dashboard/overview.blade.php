@extends('layouts.dashboard')

@section('title', 'האזור האישי | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'שלום ' . auth()->user()->first_name)
@section('page-sub', 'עמוד ההנצחה של ' . $memorial->full_name)

@section('page-actions')
    <a href="{{ route('dashboard.memories.create') }}" class="btn">הוספת זיכרון</a>
@endsection

@section('dashboard')

@if (session('memorial_created'))
    <div class="alert alert--success" style="margin-block-end: 20px;">
        העמוד נוצר בהצלחה. אפשר להתחיל להוסיף תמונות, זיכרונות ולשתף את הקישור עם המשפחה.
    </div>
@endif

<div class="grid grid--4" style="margin-block-end: 20px;">
    <div class="stat">
        <p class="stat__value">{{ $memorial->approved_count }}</p>
        <p class="stat__label">זיכרונות מפורסמים</p>
    </div>
    <div class="stat">
        <p class="stat__value">{{ $memorial->pending_count }}</p>
        <p class="stat__label">ממתינים לאישור</p>
    </div>
    <div class="stat">
        <p class="stat__value">{{ $memorial->images_count }}</p>
        <p class="stat__label">תמונות בגלריה</p>
    </div>
    <div class="stat">
        <p class="stat__value">{{ number_format($memorial->views_count) }}</p>
        <p class="stat__label">צפיות בעמוד</p>
    </div>
</div>

<div class="grid grid--2">
    <div>
        <div class="card">
            <div class="card__head">
                <h2>השלמת העמוד</h2>
                <span class="micro">{{ $completeness['done'] }} מתוך {{ $completeness['total'] }}</span>
            </div>
            <div class="card__body">
                <div class="progress" style="margin-block-end: 18px;">
                    <span style="width: {{ $completeness['percent'] }}%"></span>
                </div>
                <div class="checklist">
                    @foreach ($completeness['items'] as $item)
                        <a href="{{ $item['route'] }}" @class(['is-done' => $item['done']])>
                            <span class="mark" aria-hidden="true">{{ $item['done'] ? '✓' : '' }}</span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__head">
                <h2>שיתוף העמוד</h2>
                <a href="{{ route('dashboard.share') }}" class="micro link-underline">לכל אפשרויות השיתוף</a>
            </div>
            <div class="card__body">
                <p class="micro" style="margin-block-end: 8px;">קישור להעלאת זיכרון (לשליחה לבני משפחה וחברים)</p>
                <div class="copy-field" x-data="copyField('{{ $memorial->share_url }}')">
                    <input type="text" :value="value" x-ref="input" readonly dir="ltr">
                    <button type="button" class="btn btn--sm" @click="copy()" x-text="copied ? 'הועתק' : 'העתקה'"></button>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card__head">
                <h2>ממתינים לאישור</h2>
                <a href="{{ route('dashboard.memories.index', ['status' => 'pending']) }}" class="micro link-underline">לכל הזיכרונות</a>
            </div>
            <div class="card__body">
                @forelse ($pending as $memory)
                    <div class="memory-row">
                        @if ($memory->cover)
                            <div class="memory-row__thumb"><img src="{{ $memory->cover->thumb_url }}" alt=""></div>
                        @endif
                        <div class="memory-row__main">
                            <p class="memory-row__name">{{ $memory->author_name }}</p>
                            <p class="memory-row__date">{{ $memory->date_display }}</p>
                            <p class="memory-row__excerpt">{{ $memory->excerpt(20) }}</p>
                            <div class="memory-row__actions">
                                <form method="POST" action="{{ route('dashboard.memories.approve', $memory) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn--sm">אישור</button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.memories.reject', $memory) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn--ghost btn--sm">דחייה</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="muted" style="font-size: var(--fs-small);">אין כרגע זיכרונות שממתינים לאישור.</p>
                @endforelse
            </div>
        </div>

        @if ($recent->isNotEmpty())
            <div class="card">
                <div class="card__head"><h2>זיכרונות אחרונים</h2></div>
                <div class="card__body">
                    @foreach ($recent as $memory)
                        <div class="memory-row">
                            @if ($memory->cover)
                                <div class="memory-row__thumb"><img src="{{ $memory->cover->thumb_url }}" alt=""></div>
                            @endif
                            <div class="memory-row__main">
                                <p class="memory-row__name">{{ $memory->author_name }}</p>
                                <p class="memory-row__date">{{ $memory->date_display }}</p>
                                <p class="memory-row__excerpt">{{ $memory->excerpt(16) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
