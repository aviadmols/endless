@extends('layouts.admin')

@section('title', $memorial->full_name . ' | ניהול')
@section('page-title', $memorial->full_name)
@section('page-sub', 'בעלים: ' . ($memorial->owner?->name ?? '—'))

@section('page-actions')
    <a href="{{ route('memorials.show', $memorial) }}" target="_blank" rel="noopener" class="btn btn--ghost">צפייה בעמוד ↗</a>
@endsection

@section('admin')

<div class="grid grid--2">
    <div class="card">
        <div class="card__head"><h2>הגדרות העמוד</h2></div>
        <div class="card__body">
            <form method="POST" action="{{ route('admin.memorials.update', $memorial) }}" class="form">
                @csrf
                @method('PUT')

                <div class="field-block" style="margin-block-end: 18px;">
                    <label for="slug">כתובת העמוד</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $memorial->slug) }}" dir="ltr" required>
                    <span class="hint">{{ url('/m') }}/{{ $memorial->slug }}</span>
                </div>

                <div class="field-block" style="margin-block-end: 18px;">
                    <label for="visibility">פרטיות</label>
                    <select name="visibility" id="visibility">
                        <option value="private" @selected($memorial->visibility === 'private')>פרטי</option>
                        <option value="unlisted" @selected($memorial->visibility === 'unlisted')>לא מאונדקס</option>
                    </select>
                </div>

                <label class="toggle" style="margin-block-end: 14px;">
                    <input type="checkbox" name="require_approval" value="1" @checked($memorial->require_approval)>
                    <span class="track" aria-hidden="true"></span>
                    <span>זיכרונות דורשים אישור</span>
                </label>
                <br>
                <label class="toggle" style="margin-block-end: 20px;">
                    <input type="checkbox" name="notify_owner" value="1" @checked($memorial->notify_owner)>
                    <span class="track" aria-hidden="true"></span>
                    <span>התראות מייל לבעלים</span>
                </label>

                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="submit" class="btn">שמירה</button>
                </div>
            </form>

            <hr class="hr">

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <form method="POST" action="{{ route('admin.memorials.login-as', $memorial) }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--sm">כניסה כבעלים (לעריכה)</button>
                </form>
                <form method="POST" action="{{ route('admin.memorials.destroy', $memorial) }}" onsubmit="return confirm('להעביר את העמוד לארכיון?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn--danger btn--sm">העברה לארכיון</button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card__head"><h2>נתונים</h2></div>
            <div class="card__body">
                <table class="data">
                    <tbody>
                        <tr><th>זיכרונות</th><td>{{ $memorial->memories_count }}</td></tr>
                        <tr><th>תמונות בגלריה</th><td>{{ $memorial->images_count }}</td></tr>
                        <tr><th>צפיות</th><td>{{ $memorial->views_count }}</td></tr>
                        <tr><th>נוצר</th><td>{{ $memorial->created_at->format('d/m/Y H:i') }}</td></tr>
                        <tr><th>קישור שיתוף</th><td class="wrap-cell" dir="ltr" style="word-break: break-all;">{{ $memorial->share_url }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h2>יומן פעילות</h2></div>
            <div class="card__body table-wrap">
                <table class="data">
                    <thead><tr><th>פעולה</th><th>מי</th><th>מתי</th></tr></thead>
                    <tbody>
                        @forelse ($log as $entry)
                            <tr>
                                <td dir="ltr">{{ $entry->action }}</td>
                                <td>{{ $entry->user?->name ?? '—' }}</td>
                                <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">אין רשומות.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
