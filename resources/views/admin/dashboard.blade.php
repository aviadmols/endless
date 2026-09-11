@extends('layouts.admin')

@section('title', 'ניהול המערכת | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'ניהול המערכת')
@section('page-sub', 'סקירה כללית של עמודי ההנצחה, המשתמשים וההגדרות')

@section('admin')

@if (! $mailReady || ! $smsReady)
    <div class="alert alert--info" style="margin-block-end: 20px;">
        @unless ($mailReady)
            <p>שליחת מיילים דרך SMTP אינה מוגדרת — קודי אימות במייל נכתבים ללוג בלבד. <a href="{{ route('admin.settings.edit', 'mail') }}" class="link-underline">הגדרת SMTP</a></p>
        @endunless
        @unless ($smsReady)
            <p>שליחת SMS אינה מוגדרת — קודי אימות ב-SMS נכתבים ללוג בלבד. <a href="{{ route('admin.settings.edit', 'sms') }}" class="link-underline">הגדרת 019SMS</a></p>
        @endunless
    </div>
@endif

<div class="grid grid--4" style="margin-block-end: 24px;">
    <div class="stat"><p class="stat__value">{{ $stats['memorials'] }}</p><p class="stat__label">עמודי הנצחה</p></div>
    <div class="stat"><p class="stat__value">{{ $stats['users'] }}</p><p class="stat__label">משתמשים</p></div>
    <div class="stat"><p class="stat__value">{{ $stats['memories'] }}</p><p class="stat__label">זיכרונות</p></div>
    <div class="stat"><p class="stat__value">{{ $stats['pending'] }}</p><p class="stat__label">ממתינים לאישור</p></div>
</div>

<div class="grid grid--2">
    <div class="card">
        <div class="card__head">
            <h2>עמודי הנצחה אחרונים</h2>
            <a href="{{ route('admin.memorials.index') }}" class="micro link-underline">לכל העמודים</a>
        </div>
        <div class="card__body table-wrap">
            <table class="data">
                <thead>
                    <tr><th>שם</th><th>בעלים</th><th>זיכרונות</th><th>נוצר</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($latestMemorials as $memorial)
                        <tr>
                            <td>{{ $memorial->full_name }}</td>
                            <td>{{ $memorial->owner?->name }}</td>
                            <td>{{ $memorial->memories_count }}</td>
                            <td>{{ $memorial->created_at->format('d/m/Y') }}</td>
                            <td><a href="{{ route('admin.memorials.edit', $memorial) }}" class="link-underline">ניהול</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">עדיין אין עמודים.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <h2>פניות מעמוד הבית</h2>
            <a href="{{ route('admin.leads.index') }}" class="micro link-underline">לכל הפניות</a>
        </div>
        <div class="card__body table-wrap">
            <table class="data">
                <thead><tr><th>שם</th><th>דוא״ל</th><th>טלפון</th><th>תאריך</th></tr></thead>
                <tbody>
                    @forelse ($latestLeads as $lead)
                        <tr>
                            <td>{{ $lead->name }}</td>
                            <td dir="ltr">{{ $lead->email }}</td>
                            <td dir="ltr">{{ $lead->phone }}</td>
                            <td>{{ $lead->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">אין פניות חדשות.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
