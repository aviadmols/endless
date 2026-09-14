@extends('layouts.dashboard')

@section('title', 'ספר | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'ספר הזיכרונות')
@section('page-sub', 'הפכו את הזיכרונות והתמונות של העמוד לספר מודפס')

@section('dashboard')

@if ($counts['memories'] === 0 && $counts['photos'] === 0)
    <div class="empty">
        <h3>עדיין אין ממה להרכיב ספר</h3>
        <p>אחרי שיתווספו זיכרונות מאושרים או תמונות לגלריה, אפשר יהיה להרכיב מהם ספר.</p>
        <a href="{{ route('dashboard.share') }}" class="btn btn--sm" style="margin-block-start: 18px;">שיתוף קישור להעלאת זיכרון</a>
    </div>
@else

<form method="POST" action="{{ route('dashboard.book.update') }}" class="book-builder"
      data-book-form data-preview-url="{{ route('dashboard.book.preview') }}">
    @csrf
    @method('PUT')

    <div class="grid grid--2">
        <div>
            <div class="card">
                <div class="card__head"><h2>מה ייכנס לספר</h2></div>
                <div class="card__body">
                    <div class="choice-list">
                        @foreach (\App\Enums\BookContent::cases() as $case)
                            <label class="choice">
                                <input type="radio" name="content" value="{{ $case->value }}"
                                       @checked($content === $case) data-book-option>
                                <span class="choice__body">
                                    <span class="choice__label">{{ $case->label() }}</span>
                                    <span class="choice__hint">{{ $case->hint() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <p class="micro" style="margin-block-start: 14px;">
                        זמינים כרגע {{ $counts['memories'] }} זיכרונות מאושרים ו-{{ $counts['photos'] }} תמונות בגלריה.
                        זיכרונות שממתינים לאישור אינם נכנסים לספר.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card__head"><h2>גודל</h2></div>
                <div class="card__body">
                    <div class="size-list">
                        @foreach (\App\Enums\BookSize::cases() as $case)
                            <label class="size-option">
                                <input type="radio" name="size" value="{{ $case->value }}"
                                       @checked($size === $case) data-book-option>
                                <span class="size-option__body">
                                    <span class="size-option__shape" style="--ratio: {{ $case->ratio() }}"></span>
                                    <span class="size-option__label">{{ $case->label() }}</span>
                                    <span class="size-option__dims">{{ $case->dimensionsLabel() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__head"><h2>צבע הכריכה</h2></div>
                <div class="card__body">
                    <div class="cover-list">
                        @foreach (\App\Enums\BookCover::cases() as $case)
                            <label class="cover-option">
                                <input type="radio" name="cover" value="{{ $case->value }}"
                                       @checked($cover === $case) data-book-option>
                                <span class="cover-option__body">
                                    <span class="cover-option__swatch" style="--swatch: {{ $case->hex() }}"></span>
                                    <span class="cover-option__label">{{ $case->label() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__head"><h2>כמה עותקים</h2></div>
                <div class="card__body">
                    <div class="copies" data-copies>
                        <button type="button" class="copies__step" data-copies-down aria-label="פחות עותק">−</button>
                        <input type="number" name="copies" id="copies" min="1" max="{{ \App\Models\Book::MAX_COPIES }}"
                               value="{{ old('copies', $book->copies) }}" inputmode="numeric" data-copies-input>
                        <button type="button" class="copies__step" data-copies-up aria-label="עוד עותק">+</button>
                    </div>
                    @error('copies')<span class="error" style="display:block; margin-block-start:8px;">{{ $message }}</span>@enderror

                    <p class="micro" style="margin-block-start: 14px;">
                        ההזמנה עדיין לא פתוחה. שמירה כאן שומרת את ההרכב שבחרתם, ונעדכן אתכם ברגע שאפשר יהיה להזמין.
                    </p>

                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-block-start: 18px;">
                        <button type="submit" class="btn btn--sm">שמירת הספר</button>
                        <button type="button" class="btn btn--ghost btn--sm" disabled aria-disabled="true">
                            הזמנה — בקרוב
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card__head">
                    <h2>הדגמה</h2>
                    {{-- Without JavaScript the options still apply, one reload at a time. --}}
                    <noscript><button type="submit" formmethod="get" formaction="{{ route('dashboard.book') }}" class="btn btn--ghost btn--sm">עדכון ההדגמה</button></noscript>
                </div>
                <div class="card__body">
                    <div data-book-preview>
                        @include('dashboard.partials._book_preview', ['pages' => $pages, 'size' => $size, 'cover' => $cover, 'openAt' => $openAt])
                    </div>
                    <p class="micro center" style="margin-block-start: 14px;">
                        ההדגמה מציגה את החלוקה לעמודים לפי הגודל שנבחר. מספר העמודים הוא הערכה ויכול להשתנות מעט בהדפסה.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Editing one page is its own form: it saves against whichever page is showing. --}}
<div class="card" data-book-editor>
    <div class="card__head">
        <h2>עריכת העמוד המוצג</h2>
        @if (($book->overrides ?? []) !== [])
            <form method="POST" action="{{ route('dashboard.book.pages.reset') }}"
                  onsubmit="return confirm('כל העריכות יימחקו והספר יחזור לתוכן של עמוד ההנצחה. להמשיך?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">ביטול כל העריכות</button>
            </form>
        @endif
    </div>
    <div class="card__body">
        <p class="micro" data-editor-empty hidden>לעמוד הזה אין מה לערוך.</p>

        {{-- Photo pages: take a picture out of the book, or put it back. --}}
        <form method="POST" action="{{ route('dashboard.book.photos.update') }}" data-editor-photos hidden
              style="margin-block-end: 24px;">
            @csrf
            @method('PUT')
            <input type="hidden" name="page" value="1" data-photos-page>
            <input type="hidden" name="content" value="{{ $content->value }}" data-mirror="content">
            <input type="hidden" name="size" value="{{ $size->value }}" data-mirror="size">
            <input type="hidden" name="cover" value="{{ $cover->value }}" data-mirror="cover">

            <p class="micro" style="margin-block-end: 10px;">התמונות בעמוד הזה. הסרה מוציאה את התמונה מהספר בלבד — היא נשארת בעמוד ההנצחה.</p>
            <div class="photo-picker" data-photos-list></div>
            <button type="submit" class="btn btn--sm" style="margin-block-start: 16px;">עדכון התמונות</button>
        </form>

        <form method="POST" action="{{ route('dashboard.book.page.update') }}" data-editor-body>
            @csrf
            @method('PUT')
            <input type="hidden" name="key" value="">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="content" value="{{ $content->value }}" data-mirror="content">
            <input type="hidden" name="size" value="{{ $size->value }}" data-mirror="size">
            <input type="hidden" name="cover" value="{{ $cover->value }}" data-mirror="cover">

            <div class="form-row" data-field-row="eyebrow">
                <div class="field-block">
                    <label for="page-eyebrow">כותרת עליונה</label>
                    <input type="text" name="eyebrow" id="page-eyebrow">
                </div>
            </div>

            <div class="form-row" data-field-row="title">
                <div class="field-block">
                    <label for="page-title">כותרת</label>
                    <input type="text" name="title" id="page-title">
                </div>
            </div>

            <div class="form-row" data-field-row="body">
                <div class="field-block">
                    <label for="page-body">טקסט העמוד</label>
                    <textarea name="body" id="page-body" rows="8"></textarea>
                    <span class="hint">שורה ריקה מפרידה בין פסקאות. טקסט ארוך מהעמוד ייחתך בהדגמה.</span>
                </div>
            </div>

            <div class="form-row" data-field-row="caption">
                <div class="field-block">
                    <label for="page-caption">שורת סיום</label>
                    <input type="text" name="caption" id="page-caption">
                </div>
            </div>

            <button type="submit" class="btn btn--sm">שמירת העמוד</button>
            <p class="micro" style="margin-block-start: 12px;">
                העריכה נשמרת לעמוד הזה בלבד ואינה משנה את עמוד ההנצחה.
                מחיקת הטקסט והחזרתו למקור מבטלת את העריכה.
            </p>
        </form>

        @if ($removed)
            <hr class="hr">
            <h3 style="font-size: var(--fs-body); margin-block-end: 4px;">תמונות שהוסרו מהספר</h3>
            <p class="micro" style="margin-block-end: 12px;">בטלו את הסימון של תמונה כדי להחזיר אותה לספר.</p>

            <form method="POST" action="{{ route('dashboard.book.photos.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="content" value="{{ $content->value }}">
                <input type="hidden" name="size" value="{{ $size->value }}">

                <div class="photo-picker">
                    @foreach ($removed as $photo)
                        <label class="photo-pick is-out">
                            <input type="hidden" name="offered[]" value="{{ $photo['key'] }}">
                            <input type="checkbox" name="remove[]" value="{{ $photo['key'] }}" checked>
                            <img src="{{ $photo['url'] }}" alt="" loading="lazy">
                            <span class="photo-pick__mark">מחוץ לספר</span>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="btn btn--ghost btn--sm" style="margin-block-start: 16px;">עדכון</button>
            </form>
        @endif
    </div>
</div>

@endif
@endsection
