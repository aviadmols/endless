@extends('layouts.app')

@section('title', 'העלאת זיכרון | ' . $memorial->full_name)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="scene">
    <div class="scene__inner wrap wrap--form">

        <div class="paper-card fade-in">
            <div class="paper-card__body">

                <div class="center" style="margin-block-end: 34px;">
                    @if ($memorial->portrait_url)
                        <img src="{{ $memorial->portrait_url }}" alt="{{ $memorial->full_name }}"
                             style="width: 92px; height: 92px; border-radius: 50%; object-fit: cover; margin-inline: auto; margin-block-end: 16px; box-shadow: var(--shadow-soft);">
                    @endif
                    <p class="eyebrow" style="font-size: 13px; letter-spacing: 2px; margin-block-end: 10px;">{{ $memorial->display_subtitle }}</p>
                    <h1 class="page-title" style="font-size: 34px;">{{ $memorial->full_name }}</h1>
                    <div class="divider" style="margin-block: 16px;"></div>
                    <p class="muted" style="font-size: var(--fs-body);">שתפו זיכרון, סיפור, תמונות או סרטון. כל מילה נשמרת בעמוד ההנצחה.</p>
                </div>

                @if ($submitted)
                    <div class="alert alert--success center" style="margin-block-end: 22px;">
                        @if ($submittedApproved)
                            תודה. הזיכרון שלכם פורסם בעמוד.
                        @else
                            תודה. הזיכרון נשלח לאישור בעלי העמוד ויפורסם בקרוב.
                        @endif
                    </div>
                    <div class="center" style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="{{ route('memorials.show', $memorial) }}" class="btn btn--ghost">מעבר לעמוד ההנצחה</a>
                        <a href="{{ route('memories.create', [$memorial, $token]) }}" class="btn">העלאת זיכרון נוסף</a>
                    </div>
                @else
                    <x-alerts />

                    <form method="POST" action="{{ route('memories.store', [$memorial, $token]) }}" enctype="multipart/form-data" class="form">
                        @csrf
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">

                        <h2 class="eyebrow" style="font-size: 14px; letter-spacing: 2px; margin-block-end: 16px;">תמונות וסרטונים</h2>
                        <x-uploader name="media" :max="config('endless.uploads.memory_max_images')" />

                        <div style="margin-block-start: 30px;">
                            <h2 class="eyebrow" style="font-size: 14px; letter-spacing: 2px; margin-block-end: 16px;">הזיכרון</h2>
                            <div class="form-row">
                                <x-field name="title" label="כותרת (לא חובה)" />
                            </div>
                            <x-editor name="body" :value="old('body')" placeholder="מה תרצו לספר? רגע, סיפור, משפט שנשאר…" />
                        </div>

                        <hr class="hr">

                        <h2 class="eyebrow" style="font-size: 14px; letter-spacing: 2px; margin-block-end: 16px;">הפרטים שלכם</h2>
                        <div class="form-row form-row--2">
                            <x-field name="author_name" label="השם שלך" required />
                            <x-field name="author_email" label="אימייל (לא חובה)" type="email" />
                        </div>
                        <p class="form-note muted" style="margin-block-end: 24px;">
                            האימייל משמש רק כדי לעדכן אתכם כשהזיכרון מאושר. הוא לא מוצג בעמוד.
                        </p>

                        <button type="submit" class="btn btn--block btn--lg">שמירת הזיכרון</button>
                    </form>
                @endif
            </div>
        </div>

        <p class="center micro" style="margin-block-start: 20px;">
            העמוד פרטי ואינו מופיע במנועי חיפוש. הזיכרונות מוצגים רק לאחר אישור המשפחה.
        </p>
    </div>
</div>
@endsection
