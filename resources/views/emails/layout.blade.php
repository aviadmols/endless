<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? setting('general.site_name', 'Endless') }}</title>
</head>
<body style="margin:0; padding:0; background:#F9F8F5; font-family: 'Heebo', Arial, sans-serif; color:#1D1D20;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F9F8F5; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#FFFFFF; border-radius:20px; overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px 0; text-align:center;">
                            <p style="margin:0; font-size:18px; letter-spacing:4px; font-weight:300; text-transform:uppercase;">{{ setting('general.site_name', 'Endless') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>
                <p style="max-width:560px; margin:18px auto 0; font-size:12px; color:#8A8A8E; text-align:center;">
                    © {{ date('Y') }} {{ setting('general.site_name', 'Endless') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
