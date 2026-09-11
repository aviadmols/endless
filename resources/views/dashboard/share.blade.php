@extends('layouts.dashboard')

@section('title', 'שיתוף | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'שיתוף העמוד')
@section('page-sub', 'שלחו לבני המשפחה ולחברים קישור להעלאת זיכרונות')

@section('dashboard')

<div class="grid grid--2">
    <div>
        <div class="card">
            <div class="card__head"><h2>קישור להעלאת זיכרון</h2></div>
            <div class="card__body">
                <p class="muted" style="font-size: var(--fs-small); margin-block-end: 14px;">
                    כל מי שיפתח את הקישור יוכל להעלות זיכרון עם תמונות.
                    @if ($memorial->require_approval)
                        הזיכרונות יחכו לאישור שלכם לפני שיופיעו בעמוד.
                    @else
                        הזיכרונות מתפרסמים מיד, ללא אישור.
                    @endif
                </p>

                <div class="copy-field" x-data="copyField('{{ $memorial->share_url }}')">
                    <input type="text" :value="value" x-ref="input" readonly dir="ltr">
                    <button type="button" class="btn btn--sm" @click="copy()" x-text="copied ? 'הועתק ✓' : 'העתקה'"></button>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-block-start: 16px;">
                    <a href="{{ $whatsappShare }}" target="_blank" rel="noopener" class="btn btn--sm">שיתוף בוואטסאפ</a>
                    <a href="{{ $mailShare }}" class="btn btn--ghost btn--sm">שליחה במייל</a>
                    <a href="{{ $memorial->share_url }}" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">פתיחת הטופס ↗</a>
                </div>

                <hr class="hr">

                <form method="POST" action="{{ route('dashboard.share.regenerate') }}"
                      onsubmit="return confirm('הקישור הנוכחי יפסיק לעבוד וייווצר קישור חדש. להמשיך?')">
                    @csrf
                    <button type="submit" class="btn btn--danger btn--sm">יצירת קישור חדש</button>
                    <p class="micro" style="margin-block-start: 8px;">שימושי אם הקישור הישן הופץ בטעות.</p>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__head"><h2>קישור לעמוד ההנצחה</h2></div>
            <div class="card__body">
                <div class="copy-field" x-data="copyField('{{ $memorial->url }}')">
                    <input type="text" :value="value" x-ref="input" readonly dir="ltr">
                    <button type="button" class="btn btn--sm" @click="copy()" x-text="copied ? 'הועתק ✓' : 'העתקה'"></button>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-block-start: 16px;">
                    <a href="{{ $whatsappPage }}" target="_blank" rel="noopener" class="btn btn--sm">שיתוף בוואטסאפ</a>
                    <a href="{{ $memorial->url }}" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">צפייה בעמוד ↗</a>
                </div>
                <p class="micro" style="margin-block-start: 14px;">העמוד אינו מופיע במנועי חיפוש ונגיש רק דרך הקישור.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__head"><h2>קוד QR להעלאת זיכרון</h2></div>
        <div class="card__body">
            <div class="qr-box">
                <canvas x-data="qr('{{ $memorial->share_url }}')" x-ref="canvas" aria-label="קוד QR לטופס העלאת זיכרון"></canvas>
            </div>
            <p class="micro center" style="margin-block-start: 14px;">
                אפשר להדפיס את הקוד ולהציב אותו באזכרה או באירוע משפחתי.
            </p>
        </div>
    </div>
</div>
@endsection
