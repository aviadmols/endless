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

        <div class="card">
            <div class="card__head">
                <h2>הדגמה</h2>
                {{-- Without JavaScript the options still apply, one reload at a time. --}}
                <noscript><button type="submit" formmethod="get" formaction="{{ route('dashboard.book') }}" class="btn btn--ghost btn--sm">עדכון ההדגמה</button></noscript>
            </div>
            <div class="card__body">
                <div data-book-preview>
                    @include('dashboard.partials._book_preview', ['pages' => $pages, 'size' => $size])
                </div>
                <p class="micro center" style="margin-block-start: 14px;">
                    ההדגמה מציגה את החלוקה לעמודים לפי הגודל שנבחר. מספר העמודים הוא הערכה ויכול להשתנות מעט בהדפסה.
                </p>
            </div>
        </div>
    </div>
</form>

@endif
@endsection
