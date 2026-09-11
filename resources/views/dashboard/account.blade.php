@extends('layouts.dashboard')

@section('title', 'החשבון שלי | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'החשבון שלי')
@section('page-sub', 'הפרטים שאיתם אתם נכנסים למערכת')

@section('dashboard')

<div class="grid grid--2">
    <div class="card">
        <div class="card__head"><h2>פרטים אישיים</h2></div>
        <div class="card__body">
            <form method="POST" action="{{ route('dashboard.account.update') }}" class="form">
                @csrf
                @method('PUT')

                <div class="form-row form-row--2">
                    <x-field name="first_name" label="שם פרטי" :value="$user->first_name" required />
                    <x-field name="last_name" label="שם משפחה" :value="$user->last_name" />
                </div>

                <div class="form-row">
                    <x-field name="email" label="כתובת אימייל" type="email" :value="$user->email" required
                             hint="{{ $user->email_verified_at ? 'מאומת' : 'לא אומת עדיין' }}" />
                </div>

                <div class="form-row form-row--2">
                    <div class="field field--always">
                        <select name="country_code" id="country_code">
                            @foreach ($countries as $code => $country)
                                <option value="{{ $code }}" @selected($countryCode === (string) $code)>{{ $country['flag'] }} +{{ $code }}</option>
                            @endforeach
                        </select>
                        <label for="country_code">קידומת</label>
                    </div>
                    <x-field name="phone" label="מספר טלפון" type="tel" :value="$phoneLocal" always
                             hint="{{ $user->phone_verified_at ? 'מאומת' : 'לא אומת עדיין' }}" />
                </div>

                <button type="submit" class="btn">שמירה</button>
            </form>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card__head"><h2>כניסה למערכת</h2></div>
            <div class="card__body">
                <p style="font-size: var(--fs-small); line-height: 1.6;">
                    הכניסה מתבצעת באמצעות קוד חד-פעמי שנשלח ב-SMS או במייל. אין סיסמה לזכור.
                    אם תשנו כאן את האימייל או הטלפון — הכניסה הבאה תהיה עם הפרטים החדשים.
                </p>
                <hr class="hr">
                <p class="micro">כניסה אחרונה: {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h2>יציאה</h2></div>
            <div class="card__body">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn--ghost">יציאה מהחשבון</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
