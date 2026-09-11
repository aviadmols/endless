@component('emails.layout')
    <h1 style="margin:0 0 14px; font-size:24px; font-weight:300;">עמוד ההנצחה נוצר</h1>

    <p style="margin:0 0 18px; font-size:16px; font-weight:300; line-height:1.6;">
        עמוד ההנצחה של <strong>{{ $memorial->full_name }}</strong> מוכן.
        אפשר להוסיף תמונות, ביוגרפיה וזיכרונות, ולשתף את הקישור עם בני משפחה וחברים.
    </p>

    <p style="margin:0 0 10px; font-size:13px; color:#575757;">עמוד ההנצחה</p>
    <p style="margin:0 0 18px; font-size:14px; direction:ltr; word-break:break-all;">
        <a href="{{ $memorial->url }}" style="color:#1D1D20;">{{ $memorial->url }}</a>
    </p>

    <p style="margin:0 0 10px; font-size:13px; color:#575757;">קישור להעלאת זיכרון (לשליחה לאחרים)</p>
    <p style="margin:0 0 24px; font-size:14px; direction:ltr; word-break:break-all;">
        <a href="{{ $memorial->share_url }}" style="color:#1D1D20;">{{ $memorial->share_url }}</a>
    </p>

    <p style="margin:0 0 22px; text-align:center;">
        <a href="{{ $dashboardUrl }}" style="display:inline-block; padding:15px 30px; background:#1D1D20; color:#FFFFFF; border-radius:12px; font-size:16px; text-decoration:none;">
            לאזור האישי
        </a>
    </p>

    <p style="margin:0; font-size:13px; color:#575757; line-height:1.6;">
        העמוד פרטי ואינו מופיע במנועי חיפוש. רק מי שיקבל מכם את הקישור יוכל לצפות בו.
    </p>
@endcomponent
