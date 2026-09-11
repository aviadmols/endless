@extends('layouts.admin')

@section('title', 'עמודי הנצחה | ניהול')
@section('page-title', 'עמודי הנצחה')
@section('page-sub', $memorials->total() . ' עמודים במערכת')

@section('admin')

<div class="card">
    <div class="card__head">
        <form method="GET" action="{{ route('admin.memorials.index') }}" style="display: flex; gap: 8px; width: 100%;">
            <input type="search" name="q" value="{{ $q }}" placeholder="חיפוש לפי שם, כתובת או בעלים"
                   style="flex: 1; padding: 10px 14px; border: 1px solid var(--rule); border-radius: var(--r-input); font-family: var(--font);">
            <button type="submit" class="btn btn--sm">חיפוש</button>
        </form>
    </div>
    <div class="card__body table-wrap">
        <table class="data">
            <thead>
                <tr><th>שם</th><th>כתובת</th><th>בעלים</th><th>זיכרונות</th><th>תמונות</th><th>צפיות</th><th>נוצר</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($memorials as $memorial)
                    <tr>
                        <td>{{ $memorial->full_name }}</td>
                        <td dir="ltr">/m/{{ $memorial->slug }}</td>
                        <td>{{ $memorial->owner?->name }}<br><span class="micro" dir="ltr">{{ $memorial->owner?->email }}</span></td>
                        <td>{{ $memorial->memories_count }}</td>
                        <td>{{ $memorial->images_count }}</td>
                        <td>{{ $memorial->views_count }}</td>
                        <td>{{ $memorial->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('admin.memorials.edit', $memorial) }}" class="link-underline">ניהול</a>
                            ·
                            <a href="{{ route('memorials.show', $memorial) }}" target="_blank" rel="noopener" class="link-underline">צפייה</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">לא נמצאו עמודים.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $memorials->links('partials.pagination') }}
@endsection
