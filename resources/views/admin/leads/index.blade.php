@extends('layouts.admin')

@section('title', 'פניות | ניהול')
@section('page-title', 'פניות מעמוד הבית')
@section('page-sub', $leads->total() . ' פניות')

@section('admin')

<div class="card">
    <div class="card__body table-wrap">
        <table class="data">
            <thead><tr><th>שם</th><th>דוא״ל</th><th>טלפון</th><th>הודעה</th><th>תאריך</th><th>סטטוס</th><th></th></tr></thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td>{{ $lead->name }}</td>
                        <td dir="ltr"><a href="mailto:{{ $lead->email }}" class="link-underline">{{ $lead->email }}</a></td>
                        <td dir="ltr">{{ $lead->phone }}</td>
                        <td class="wrap-cell">{{ $lead->message }}</td>
                        <td>{{ $lead->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge {{ $lead->handled ? 'badge--approved' : 'badge--pending' }}">{{ $lead->handled ? 'טופל' : 'פתוח' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.leads.handled', $lead) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--sm">{{ $lead->handled ? 'סימון כפתוח' : 'סימון כטופל' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">אין פניות.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $leads->links('partials.pagination') }}
@endsection
