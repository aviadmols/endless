@component('emails.layout')
    <h1 style="margin:0 0 14px; font-size:24px; font-weight:300;">
        {{ $purpose === 'register' ? 'אימות יצירת העמוד' : 'קוד הכניסה שלך' }}
    </h1>

    <p style="margin:0 0 22px; font-size:16px; font-weight:300; line-height:1.6;">
        הזינו את הקוד הבא כדי להמשיך. הקוד תקף ל-{{ $ttl }} דקות.
    </p>

    <p style="margin:0 0 22px; text-align:center;">
        <span style="display:inline-block; padding:18px 34px; background:#F3F1F1; border-radius:12px; font-size:32px; letter-spacing:10px; font-weight:400; direction:ltr;">{{ $code }}</span>
    </p>

    <p style="margin:0; font-size:13px; color:#575757; line-height:1.6;">
        אם לא ביקשתם את הקוד, אפשר להתעלם מההודעה. הקוד תקף לשימוש יחיד.
    </p>
@endcomponent
