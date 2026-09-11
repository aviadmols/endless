<footer class="site-footer">
    <div class="site-footer__inner">
        <p>© {{ date('Y') }} כל הזכויות שמורות ל-{{ setting('general.site_name', 'Endless') }}</p>
        <p>
            <a href="{{ route('home') }}">דף הבית</a>
            <span aria-hidden="true"> | </span>
            <a href="{{ route('register') }}">יצירת עמוד הנצחה</a>
            @if (setting('general.contact_email'))
                <span aria-hidden="true"> | </span>
                <a href="mailto:{{ setting('general.contact_email') }}">יצירת קשר</a>
            @endif
        </p>
    </div>
</footer>
