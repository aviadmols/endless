@extends('layouts.app')

@section('title', 'אימות קוד | ' . setting('general.site_name', 'Endless'))

@section('content')
<div class="auth">
    <div class="auth__form">
        <div class="auth__form-inner" x-data="{ seconds: {{ (int) $resendIn }}, tick() { if (this.seconds > 0) { this.seconds--; setTimeout(() => this.tick(), 1000); } } }" x-init="tick()">
            <h1 class="page-title">קוד אימות</h1>
            <p class="lede" style="margin-block: 16px 28px;">
                שלחנו קוד בן {{ config('endless.otp.length', 6) }} ספרות
                {{ $channel === \App\Enums\OtpChannel::Sms ? 'ב-SMS אל' : 'למייל' }}
                <strong dir="ltr">{{ $identifierMasked }}</strong>.
                הקוד תקף ל-{{ config('endless.otp.ttl_minutes', 10) }} דקות.
            </p>

            <x-alerts />

            @if ($devCode)
                <div class="alert alert--info" style="margin-block-end: 20px;">
                    סביבת פיתוח: הקוד הקבוע הוא <strong dir="ltr">{{ $devCode }}</strong>
                </div>
            @endif

            <form method="POST" action="{{ $verifyRoute }}" class="form">
                @csrf
                <div class="form-row">
                    <x-field
                        name="code"
                        label="קוד האימות"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="{{ config('endless.otp.length', 6) }}"
                        required
                        autofocus
                        dir="ltr"
                        style="text-align: center; letter-spacing: 8px; font-size: 22px;"
                    />
                </div>

                <button type="submit" class="btn btn--block btn--lg">כניסה</button>
            </form>

            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-block-start: 22px;">
                <form method="POST" action="{{ $resendRoute }}">
                    @csrf
                    <input type="hidden" name="channel" value="{{ $channel->value }}">
                    <button type="submit" class="btn btn--ghost btn--sm" :disabled="seconds > 0">
                        <span x-show="seconds === 0">שליחת קוד חדש</span>
                        <span x-show="seconds > 0" x-cloak>שליחה חוזרת בעוד <span x-text="seconds"></span> שניות</span>
                    </button>
                </form>

                @if ($alternateChannel)
                    <form method="POST" action="{{ $resendRoute }}">
                        @csrf
                        <input type="hidden" name="channel" value="{{ $alternateChannel->value }}">
                        <button type="submit" class="btn btn--ghost btn--sm">
                            שליחת הקוד ב{{ $alternateChannel === \App\Enums\OtpChannel::Sms ? '-SMS' : 'מייל' }} במקום
                        </button>
                    </form>
                @endif
            </div>

            <p class="micro" style="margin-block-start: 24px;">
                <a href="{{ $purpose === 'login' ? route('login') : route('register') }}" class="link-underline">שינוי הפרטים</a>
            </p>
        </div>
    </div>

    <div class="auth__art" style="background-image: var(--scene-image);" aria-hidden="true"></div>
</div>
@endsection
