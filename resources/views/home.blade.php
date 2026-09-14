@extends('layouts.app')

@section('title', setting('general.site_name', 'Endless') . ' | אוספים זיכרונות')

@section('body-class', 'home')
@section('main-class', 'page-body--flush')

@section('content')

{{-- ------------------------------------------------------------------ hero --}}
<section class="home-hero">
    <div class="home-hero__text">
      <div class="home-hero__inner">
        <h1 class="home-display home-hero__title">אוספים זיכרונות</h1>

        <p class="home-lede home-hero__sub">
            אדם אחד, אינספור זיכרונות. מרחב משותף שנבנה מכל מי שנגע בו —
            חברים, משפחה ואהובים שהיו חלק מהדרך שלו.
        </p>

        <div class="home-hero__actions">
            <a href="{{ route('register') }}" class="btn">יצירת עמוד</a>
            <a href="#how" class="btn btn--ghost">איך זה עובד</a>
        </div>

        <ul class="home-assures">
            <li><x-home-icon icon="whatsapp" /> מאובטח ופרטי — מבוסס וואטסאפ</li>
            <li><x-home-icon icon="private" /> בלי פרסומות. בלי ספאם. רק זיכרונות.</li>
            <li><x-home-icon icon="heart" /> מתחילים מתי שרוצים, בלי התחייבות</li>
        </ul>
      </div>
    </div>

    <div class="home-hero__media" role="img" aria-label="דמות יושבת על ענן מול הזריחה"></div>
</section>

{{-- ------------------------------------------------------------ who it is for --}}
<section class="home-strip">
    <div class="home-wrap home-strip__grid">
        <div class="home-strip__item fade-in">
            <x-home-icon icon="remember" />
            <p>Endless הוא לכל מי שאיבד אדם אהוב וממשיך לזכור</p>
        </div>
        <div class="home-strip__item fade-in">
            <x-home-icon icon="anyone" />
            <p>בין אם אתם משפחה, חברים, עמיתים לעבודה או סתם חלקתם רגע</p>
        </div>
        <div class="home-strip__item fade-in">
            <x-home-icon icon="space" />
            <p>זה המרחב שלכם לזכור, לשתף ולשמור על הזיכרון חי</p>
        </div>
    </div>
</section>

{{-- ---------------------------------------------------------------- how it works --}}
<section class="home-how" id="how">
    <div class="home-wrap">
        <h2 class="home-display home-how__title">איך זה עובד</h2>

        <div class="stack-cards" data-stack-cards>
            <article class="stack-card" style="--n: 1">
                <div class="stack-card__inner">
                    <div class="stack-card__text">
                        <h3>יוצרים עמוד</h3>
                        <p>מתחילים בפתיחת עמוד הנצחה — מרחב משותף לאיסוף סיפורים, זיכרונות ורגעים של אהבה.</p>
                    </div>
                    <figure class="stack-card__figure">
                        <img src="{{ asset('images/home/step-1.jpg') }}" alt="" width="1400" height="882" fetchpriority="low">
                    </figure>
                </div>
            </article>

            <article class="stack-card" style="--n: 2">
                <div class="stack-card__inner">
                    <div class="stack-card__text">
                        <h3>משתפים את הקישור</h3>
                        <p>כשהעמוד מוכן תקבלו קישור אישי לוואטסאפ. שתפו אותו כדי שאחרים יוכלו להוסיף את הזיכרונות שלהם, כל אחד בדרכו.</p>
                    </div>
                    <figure class="stack-card__figure">
                        <img src="{{ asset('images/home/step-2.jpg') }}" alt="" width="1400" height="933" loading="lazy">
                    </figure>
                </div>
            </article>

            <article class="stack-card" style="--n: 3">
                <div class="stack-card__inner">
                    <div class="stack-card__text">
                        <h3>מאשרים ואוצרים</h3>
                        <p>כבעלי העמוד אתם עוברים ומאשרים כל זיכרון, ושומרים על מרחב מכבד, רגיש ונאמן לרוח שלו.</p>
                    </div>
                    <figure class="stack-card__figure">
                        <img src="{{ asset('images/home/step-3.jpg') }}" alt="" width="1400" height="933" loading="lazy">
                    </figure>
                </div>
            </article>

            <article class="stack-card" style="--n: 4">
                <div class="stack-card__inner">
                    <div class="stack-card__text">
                        <h3>מחווה שנשארת</h3>
                        <p>התוצאה היא עמוד אחד ומאוחד, מלא בזיכרונות מגוונים — פסיפס של רגעים, סיפורים ואהבה.</p>
                    </div>
                    <figure class="stack-card__figure">
                        <img src="{{ asset('images/home/step-4.jpg') }}" alt="" width="1400" height="934" loading="lazy">
                    </figure>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- ------------------------------------------------------------- why endless --}}
<section class="home-why" id="why">
    <div class="home-why__text">
      <div class="home-why__inner">
        <h2 class="home-display home-why__title">למה Endless</h2>

        <p class="home-lede home-why__lede">
            Endless הופך את הזכירה לחוויה משותפת. במקום שאדם אחד יעשה הכול,
            כל אחד יכול לתרום דרך קישור פשוט.
        </p>

        <div class="home-why__grid">
            <div class="home-why__item fade-in">
                <x-home-icon icon="collective" />
                <h3>סיפור משותף</h3>
                <p>לא רק המילים שלכם, כל אחד מוסיף את שלו</p>
            </div>
            <div class="home-why__item fade-in">
                <x-home-icon icon="effortless" />
                <h3>שיתוף ללא מאמץ</h3>
                <p>בלי חשבונות ובלי הורדות, רק קישור</p>
            </div>
            <div class="home-why__item fade-in">
                <x-home-icon icon="growing" />
                <h3>תמיד ממשיך לגדול</h3>
                <p>אפשר להוסיף זיכרונות בכל זמן, גם שנים אחרי</p>
            </div>
            <div class="home-why__item fade-in">
                <x-home-icon icon="safe" />
                <h3>בטוח ופרטי</h3>
                <p>אתם בוחרים מי תורם ומה מוצג</p>
            </div>
        </div>
      </div>
    </div>

    <div class="home-why__media" role="img" aria-label="דמות יושבת על ענן מול הזריחה"></div>
</section>

{{-- ---------------------------------------------------------------- stories --}}
<section class="home-stories">
    <div class="home-wrap">
        <h2 class="home-display home-stories__title">סיפורים שנשארים איתכם</h2>

        <div class="home-stories__grid">
            <figure class="home-story fade-in" style="background-image: url('{{ asset('images/home/story-1.jpg') }}')">
                <img src="{{ asset('images/home/quote.svg') }}" alt="" width="60" height="48" loading="lazy">
                <blockquote>היה מנחם לראות בכמה חיים הוא נגע. ההודעות המשיכו להגיע במשך שבועות.</blockquote>
                <figcaption>רחל, תל אביב</figcaption>
            </figure>

            <figure class="home-story fade-in" style="background-image: url('{{ asset('images/home/story-2.jpg') }}')">
                <img src="{{ asset('images/home/quote.svg') }}" alt="" width="60" height="48" loading="lazy">
                <blockquote>עמוד ההנצחה של אבא שלי הפך למשהו שאני קוראת כל ערב. גיליתי סיפורים שאפילו אני לא הכרתי.</blockquote>
                <figcaption>בן, ירושלים</figcaption>
            </figure>
        </div>
    </div>
</section>

{{-- -------------------------------------------------------------------- cta --}}
<section class="home-cta">
    <div class="home-wrap">
        <h2 class="home-display home-cta__title">מרחב לזיכרון, להוקרה ולחיבור</h2>

        <p class="home-lede home-cta__sub">
            צרו מרחב משמעותי כבר היום.<br class="br-desktop">
            לוקח פחות מדקה להתחיל.
        </p>

        <a href="{{ route('register') }}" class="btn">יצירת עמוד</a>
    </div>
</section>

@endsection
