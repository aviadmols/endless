@component('emails.layout')
    <h1 style="margin:0 0 14px; font-size:24px; font-weight:300;">פנייה חדשה מעמוד הבית</h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;">
        <tr><td style="padding:6px 0; color:#575757; width:90px;">שם</td><td style="padding:6px 0;">{{ $lead->name }}</td></tr>
        <tr><td style="padding:6px 0; color:#575757;">דוא״ל</td><td style="padding:6px 0; direction:ltr;">{{ $lead->email }}</td></tr>
        <tr><td style="padding:6px 0; color:#575757;">טלפון</td><td style="padding:6px 0; direction:ltr;">{{ $lead->phone ?: '—' }}</td></tr>
        <tr><td style="padding:6px 0; color:#575757;">התקבל</td><td style="padding:6px 0;">{{ $lead->created_at->format('d/m/Y H:i') }}</td></tr>
    </table>

    @if ($lead->message)
        <div style="background:#F9F8F5; border-radius:12px; padding:16px 18px; margin-top:18px;">
            <p style="margin:0; font-size:15px; font-weight:300; line-height:1.6;">{{ $lead->message }}</p>
        </div>
    @endif
@endcomponent
