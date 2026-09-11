@extends('layouts.app')

@section('title', 'כניסה | ' . setting('general.site_name', 'Endless'))

@section('content')
<div class="auth">
    <div class="auth__form">
        <div class="auth__form-inner">
            <h1 class="page-title">כניסה</h1>
            <p class="lede" style="margin-block: 16px 28px;">
                הזינו את הטלפון או האימייל שאיתם נרשמתם, ונשלח אליכם קוד כניסה חד-פעמי.
            </p>

            <x-alerts />

            <form method="POST" action="{{ route('login.send') }}" class="form">
                @csrf

                <div class="form-row" style="flex-direction: row; align-items: flex-start;">
                    <div class="field field--always" style="flex: 0 0 130px;">
                        <select name="country_code" id="login_country_code">
                            @foreach ($countries as $code => $country)
                                <option value="{{ $code }}" @selected(old('country_code', config('endless.phone.default_country')) === (string) $code)>
                                    {{ $country['flag'] }} +{{ $code }}
                                </option>
                            @endforeach
                        </select>
                        <label for="login_country_code">קידומת</label>
                    </div>

                    <div style="flex: 1 1 auto; min-width: 0;">
                        <x-field
                            name="identifier"
                            label="טלפון או אימייל"
                            :value="session('prefill')"
                            required
                            autocomplete="username"
                        />
                    </div>
                </div>

                <p class="micro" style="margin-block-end: 22px;">הקידומת רלוונטית רק כשמזינים מספר טלפון.</p>

                <button type="submit" class="btn btn--block btn--lg">שליחת קוד כניסה</button>
            </form>

            <p class="micro" style="margin-block-start: 24px;">
                אין לכם עדיין עמוד? <a href="{{ route('register') }}" class="link-underline">יצירת עמוד הנצחה</a>
            </p>
        </div>
    </div>

    <div class="auth__art" style="background-image: var(--scene-image);" aria-hidden="true"></div>
</div>
@endsection
