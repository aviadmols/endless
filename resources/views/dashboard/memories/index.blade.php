@extends('layouts.dashboard')

@section('title', 'זיכרונות | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'זיכרונות')
@section('page-sub', 'אישור, עריכה והסתרה של זיכרונות שהועלו לעמוד')

@section('page-actions')
    <a href="{{ route('dashboard.memories.create') }}" class="btn">הוספת זיכרון</a>
@endsection

@section('dashboard')

<div class="tabs" style="margin-block-end: 20px;">
    @foreach ([
        'all' => 'הכל',
        'pending' => 'ממתינים',
        'approved' => 'מאושרים',
        'rejected' => 'נדחו',
    ] as $key => $label)
        <a href="{{ route('dashboard.memories.index', ['status' => $key]) }}" @class(['is-active' => $status === $key])>
            {{ $label }} <span class="count">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

<div class="card">
    <div class="card__body">
        @forelse ($memories as $memory)
            <div class="memory-row">
                @if ($memory->cover)
                    <div class="memory-row__thumb"><img src="{{ $memory->cover->thumb_url }}" alt=""></div>
                @endif
                <div class="memory-row__main">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                        <p class="memory-row__name">{{ $memory->author_name }}</p>
                        <span class="badge badge--{{ $memory->status->value }}">{{ $memory->status->label() }}</span>
                        @if ($memory->media->count() > 1)
                            <span class="micro">{{ $memory->media->count() }} קבצים</span>
                        @endif
                    </div>
                    <p class="memory-row__date">{{ $memory->date_display }}@if ($memory->author_email) · {{ $memory->author_email }}@endif</p>
                    <p class="memory-row__excerpt">{{ $memory->excerpt(35) }}</p>

                    <div class="memory-row__actions">
                        @if ($memory->status !== \App\Enums\MemoryStatus::Approved)
                            <form method="POST" action="{{ route('dashboard.memories.approve', $memory) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--sm">אישור ופרסום</button>
                            </form>
                        @endif
                        @if ($memory->status !== \App\Enums\MemoryStatus::Rejected)
                            <form method="POST" action="{{ route('dashboard.memories.reject', $memory) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--sm">הסתרה</button>
                            </form>
                        @endif
                        <a href="{{ route('dashboard.memories.edit', $memory) }}" class="btn btn--ghost btn--sm">עריכה</a>
                        <a href="{{ route('memories.show', [$memorial, $memory]) }}" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">צפייה ↗</a>
                        <form method="POST" action="{{ route('dashboard.memories.destroy', $memory) }}" onsubmit="return confirm('למחוק את הזיכרון לצמיתות?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">מחיקה</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <h3>אין כאן זיכרונות</h3>
                <p style="margin-block-end: 20px;">שתפו את הקישור עם בני משפחה וחברים כדי שיעלו זיכרונות.</p>
                <a href="{{ route('dashboard.share') }}" class="btn">לקישור השיתוף</a>
            </div>
        @endforelse
    </div>
</div>

{{ $memories->links('partials.pagination') }}
@endsection
