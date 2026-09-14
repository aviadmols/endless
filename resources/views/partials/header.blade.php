<header class="site-header">
    <div class="site-header__inner">
        <a href="{{ route('home') }}" class="logo" aria-label="{{ setting('general.site_name') }}">
            <img src="{{ brand_logo_url() }}" alt="{{ setting('general.site_name', 'Endless') }}">
        </a>

        <div class="header-actions">
            @auth
                <nav class="site-nav" aria-label="ניווט ראשי">
                    <a href="{{ route('dashboard.index') }}">האזור האישי</a>
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.index') }}">ניהול</a>
                    @endif
                </nav>
                <form method="POST" action="{{ route('logout') }}" class="hide-mobile">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--sm">יציאה</button>
                </form>
            @else
                <nav class="site-nav" aria-label="ניווט ראשי">
                    <a href="{{ route('home') }}#how">איך זה עובד</a>
                    <a href="{{ route('shiryon') }}">חייל השריון</a>
                    <a href="{{ route('login') }}">כניסה</a>
                </nav>
                <a href="{{ route('register') }}" class="btn btn--ghost btn--sm">יצירת עמוד</a>
            @endauth

            <button type="button" class="burger" data-burger aria-expanded="false" aria-controls="mobile-menu" aria-label="תפריט">
                <span></span>
            </button>
        </div>
    </div>
</header>

<nav class="mobile-menu" id="mobile-menu" data-mobile-menu hidden aria-label="תפריט נייד">
    @auth
        <a href="{{ route('dashboard.index') }}">האזור האישי</a>
        <a href="{{ route('dashboard.memorial.edit') }}">עריכת העמוד</a>
        <a href="{{ route('dashboard.memories.index') }}">זיכרונות</a>
        <a href="{{ route('dashboard.share') }}">שיתוף</a>
        <a href="{{ route('dashboard.account.edit') }}">החשבון שלי</a>
        @if (auth()->user()->is_admin)
            <a href="{{ route('admin.index') }}">ניהול המערכת</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn--ghost btn--block">יציאה</button>
        </form>
    @else
        <a href="{{ route('home') }}">דף הבית</a>
        <a href="{{ route('home') }}#how">איך זה עובד</a>
        <a href="{{ route('home') }}#why">למה Endless</a>
        <a href="{{ route('shiryon') }}">חייל השריון</a>
        <a href="{{ route('login') }}">כניסה לאזור האישי</a>
        <a href="{{ route('register') }}" class="btn btn--block">יצירת עמוד הנצחה</a>
    @endauth
</nav>
