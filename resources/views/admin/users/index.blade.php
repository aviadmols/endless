@extends('layouts.admin')

@section('title', 'משתמשים | ניהול')
@section('page-title', 'משתמשים')
@section('page-sub', $users->total() . ' משתמשים רשומים')

@section('admin')

<div class="card">
    <div class="card__head">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display: flex; gap: 8px; width: 100%;">
            <input type="search" name="q" value="{{ $q }}" placeholder="חיפוש לפי שם, אימייל או טלפון"
                   style="flex: 1; padding: 10px 14px; border: 1px solid var(--rule); border-radius: var(--r-input); font-family: var(--font);">
            <button type="submit" class="btn btn--sm">חיפוש</button>
        </form>
    </div>
    <div class="card__body table-wrap">
        <table class="data">
            <thead><tr><th>שם</th><th>אימייל</th><th>טלפון</th><th>עמודים</th><th>כניסה אחרונה</th><th>הרשאה</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td dir="ltr">{{ $user->email }}</td>
                        <td dir="ltr">{{ \App\Support\PhoneNumber::format($user->phone) }}</td>
                        <td>{{ $user->memorials_count }}</td>
                        <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $user->is_admin ? 'מנהל' : 'משתמש' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--sm">{{ $user->is_admin ? 'הסרת ניהול' : 'הגדרה כמנהל' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">לא נמצאו משתמשים.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $users->links('partials.pagination') }}
@endsection
