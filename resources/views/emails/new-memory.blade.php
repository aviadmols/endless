@component('emails.layout')
    <h1 style="margin:0 0 14px; font-size:24px; font-weight:300;">זיכרון חדש הועלה</h1>

    <p style="margin:0 0 18px; font-size:16px; font-weight:300; line-height:1.6;">
        {{ $memory->author_name }} העלה/תה זיכרון לעמוד ההנצחה של <strong>{{ $memorial->full_name }}</strong>.
    </p>

    <div style="background:#F9F8F5; border-radius:12px; padding:18px 20px; margin-bottom:22px;">
        <p style="margin:0; font-size:15px; font-weight:300; line-height:1.6;">{{ $memory->excerpt(40) }}</p>
        @if ($memory->images->isNotEmpty())
            <p style="margin:12px 0 0; font-size:13px; color:#575757;">מצורפות {{ $memory->images->count() }} תמונות.</p>
        @endif
    </div>

    <p style="margin:0 0 22px; text-align:center;">
        <a href="{{ $dashboardUrl }}" style="display:inline-block; padding:15px 30px; background:#1D1D20; color:#FFFFFF; border-radius:12px; font-size:16px; text-decoration:none;">
            {{ $memorial->require_approval ? 'אישור הזיכרון' : 'צפייה בזיכרון' }}
        </a>
    </p>

    <p style="margin:0; font-size:13px; color:#575757; line-height:1.6;">
        @if ($memorial->require_approval)
            הזיכרון יופיע בעמוד רק לאחר אישורכם.
        @else
            הזיכרון כבר מוצג בעמוד. תמיד אפשר להסתיר אותו מהאזור האישי.
        @endif
    </p>
@endcomponent
