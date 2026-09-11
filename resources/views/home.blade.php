@extends('layouts.app')

@section('title', setting('general.site_name', 'Endless') . ' | עמוד הנצחה')

@section('content')
<div class="landing">
    <div class="landing__bg" aria-hidden="true"></div>

    <div class="landing__inner wrap wrap--form">
        <div class="paper-card fade-in">
            <div class="paper-card__body">

                @if (setting('landing.partner_logo_1') || setting('landing.partner_logo_2'))
                    <div class="partners">
                        @if (setting('landing.partner_logo_1'))
                            <img src="{{ media_url(setting('landing.partner_logo_1')) }}" alt="">
                        @endif
                        @if (setting('landing.partner_logo_2'))
                            <img src="{{ media_url(setting('landing.partner_logo_2')) }}" alt="">
                        @endif
                    </div>
                    <hr class="hr">
                @endif

                <h1 style="font-size: 24px; font-weight: var(--fw-medium); line-height: 1.2; margin-block-end: 18px;">
                    {{ $landing['title'] ?? 'משפחה יקרה,' }}
                </h1>

                <div class="rich">{!! $landing['intro'] ?? '' !!}</div>

                @if ($features)
                    <div class="feature-grid" style="margin-block: 40px;">
                        @foreach ($features as $feature)
                            <div class="feature fade-in">
                                <div class="feature__icon"><x-feature-icon :icon="$feature['icon'] ?? 'dove'" /></div>
                                <p class="feature__text">{{ $feature['text'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (setting('landing.image_path'))
                    <img src="{{ media_url(setting('landing.image_path')) }}" alt="" style="width: 100%; border-radius: var(--r-img); margin-block: 30px;" class="fade-in">
                @endif

                <section class="fade-in" style="margin-block-start: 40px;">
                    <h2 style="font-size: 24px; font-weight: var(--fw-medium); margin-block-end: 14px;">{{ $landing['how_title'] ?? 'איך זה עובד' }}</h2>
                    <p style="font-size: var(--fs-body-lg); font-weight: var(--fw-light); line-height: var(--lh-body);">{{ $landing['how_intro'] ?? '' }}</p>

                    @if ($howList)
                        <p style="font-weight: var(--fw-medium); margin-block: 18px 10px;">{{ $landing['how_list_title'] ?? '' }}</p>
                        <ul style="padding-inline-start: 1.2em;">
                            @foreach ($howList as $item)
                                <li style="font-size: var(--fs-body-lg); font-weight: var(--fw-light); line-height: var(--lh-body); margin-block-end: 8px;">{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($landing['note'] ?? null)
                        <p style="font-weight: var(--fw-medium); margin-block-start: 20px;">{{ $landing['note'] }}</p>
                    @endif
                </section>

                <div style="display: flex; justify-content: center; margin-block-start: 34px;">
                    <a href="{{ route('register') }}" class="btn btn--lg">יצירת עמוד הנצחה</a>
                </div>
            </div>

            <div id="contact" class="lead-form" style="border-radius: 0;">
                <h2 class="center" style="font-size: 24px; font-weight: var(--fw-medium); margin-block-end: 10px;">{{ $landing['form_title'] ?? 'אנחנו מחכים לכם' }}</h2>
                <p class="center muted" style="font-size: var(--fs-body); margin-block-end: 22px;">{{ $landing['form_text'] ?? '' }}</p>

                @if (session('lead_sent'))
                    <div class="alert alert--success center">קיבלנו את הפרטים. נחזור אליכם בהקדם עם קישור מאובטח.</div>
                @else
                    <x-alerts />
                    <form method="POST" action="{{ route('leads.store') }}" class="lead-form__row">
                        @csrf
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">
                        <x-field name="name" label="שם מלא" required />
                        <x-field name="email" label="דוא״ל" type="email" required />
                        <x-field name="phone" label="טלפון" type="tel" />
                        <button type="submit" class="btn">שליחה</button>
                    </form>
                @endif
            </div>

            <div class="paper-card__body center" style="padding-block: 34px;">
                <p class="logo" style="font-size: 20px; margin-block-end: 16px;">{{ setting('general.site_name', 'Endless') }}</p>
                <p class="muted" style="white-space: pre-line; font-size: var(--fs-body); line-height: 1.6;">{{ $landing['signature'] ?? '' }}</p>
                <p style="white-space: pre-line; margin-block-start: 16px; font-weight: var(--fw-medium);">{{ $landing['signature_name'] ?? '' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
