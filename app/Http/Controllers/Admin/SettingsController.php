<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Services\Mail\DynamicMailConfigurator;
use App\Services\Media\ImageProcessor;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\SmsManager;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsRepository $settings,
        protected ImageProcessor $images,
    ) {}

    /** Field definitions per settings group (rendered generically by the admin form). */
    public function groups(): array
    {
        return [
            'general' => [
                'title' => 'הגדרות כלליות',
                'intro' => 'שם האתר, כתובת ליצירת קשר והתראות למנהל.',
                'fields' => [
                    'general.site_name' => ['label' => 'שם האתר', 'type' => 'text'],
                    'general.contact_email' => ['label' => 'אימייל למנהל (התראות, לידים)', 'type' => 'email'],
                    'general.notify_admin_on_register' => ['label' => 'שליחת מייל למנהל על כל עמוד הנצחה חדש', 'type' => 'toggle'],
                    'general.logo_path' => ['label' => 'לוגו (SVG/PNG, מוצג בכותרת במקום הטקסט)', 'type' => 'image'],
                    'general.side_image' => ['label' => 'תמונת הרקע בחצי המסך (הרשמה, כניסה, אזור אישי)', 'type' => 'image'],
                ],
            ],
            'mail' => [
                'title' => 'הגדרות SMTP (דואר יוצא)',
                'intro' => 'קודי אימות במייל, התראות על זיכרונות חדשים ומיילים למנהל נשלחים דרך שרת ה-SMTP הזה. כל עוד ההגדרה כבויה, המיילים נכתבים ללוג בלבד.',
                'fields' => [
                    'mail.enabled' => ['label' => 'שליחת מיילים דרך SMTP פעילה', 'type' => 'toggle'],
                    'mail.host' => ['label' => 'שרת SMTP (host)', 'type' => 'text', 'placeholder' => 'smtp.gmail.com'],
                    'mail.port' => ['label' => 'פורט', 'type' => 'number', 'placeholder' => '587'],
                    'mail.encryption' => ['label' => 'הצפנה', 'type' => 'select', 'options' => ['tls' => 'TLS / STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'ללא']],
                    'mail.username' => ['label' => 'שם משתמש', 'type' => 'text'],
                    'mail.password' => ['label' => 'סיסמה', 'type' => 'secret'],
                    'mail.from_address' => ['label' => 'כתובת השולח (From)', 'type' => 'email'],
                    'mail.from_name' => ['label' => 'שם השולח', 'type' => 'text'],
                ],
                'test' => ['route' => 'admin.settings.mail.test', 'label' => 'שליחת מייל בדיקה', 'input' => 'email', 'placeholder' => 'name@example.com'],
            ],
            'sms' => [
                'title' => 'הגדרות SMS (019SMS)',
                'intro' => 'קודי אימות ב-SMS נשלחים דרך חשבון ה-API של 019SMS. הזינו את שם המשתמש ואת הטוקן שנוצר בפאנל של 019 (או סיסמה בחשבונות ישנים), ואת שם/מספר השולח המאושר.',
                'fields' => [
                    'sms.enabled' => ['label' => 'שליחת SMS פעילה', 'type' => 'toggle'],
                    'sms.driver' => ['label' => 'ספק', 'type' => 'select', 'options' => ['019sms' => '019SMS (019sms.co.il)', 'log' => 'לוג בלבד (לבדיקות)']],
                    'sms.019.username' => ['label' => 'שם משתמש ב-019', 'type' => 'text'],
                    'sms.019.token' => ['label' => 'API Token (Bearer)', 'type' => 'secret'],
                    'sms.019.password' => ['label' => 'סיסמה (רק אם אין טוקן)', 'type' => 'secret'],
                    'sms.019.source' => ['label' => 'שם / מספר השולח', 'type' => 'text', 'placeholder' => 'Endless או 0501234567'],
                    'otp.preferred_channel' => ['label' => 'ערוץ מועדף לקוד אימות', 'type' => 'select', 'options' => ['auto' => 'אוטומטי (SMS אם זמין, אחרת מייל)', 'sms' => 'SMS', 'email' => 'מייל']],
                ],
                'test' => ['route' => 'admin.settings.sms.test', 'label' => 'שליחת SMS בדיקה', 'input' => 'tel', 'placeholder' => '050-0000000'],
            ],
            'landing' => [
                'title' => 'תוכן עמוד הבית',
                'intro' => 'הטקסטים והתמונות של עמוד הנחיתה (בסגנון "יד לשריון").',
                'fields' => [
                    'landing.partner_logo_1' => ['label' => 'לוגו שותף 1', 'type' => 'image'],
                    'landing.partner_logo_2' => ['label' => 'לוגו שותף 2', 'type' => 'image'],
                    'landing.title' => ['label' => 'כותרת פתיחה', 'type' => 'text'],
                    'landing.intro' => ['label' => 'פסקאות פתיחה', 'type' => 'richtext'],
                    'landing.features' => ['label' => 'ארבע התכונות (אייקון + טקסט)', 'type' => 'features'],
                    'landing.image_path' => ['label' => 'תמונה מרכזית', 'type' => 'image'],
                    'landing.how_title' => ['label' => 'כותרת "איך זה עובד"', 'type' => 'text'],
                    'landing.how_intro' => ['label' => 'טקסט "איך זה עובד"', 'type' => 'textarea'],
                    'landing.how_list_title' => ['label' => 'כותרת הרשימה', 'type' => 'text'],
                    'landing.how_list' => ['label' => 'שורות הרשימה (שורה לכל פריט)', 'type' => 'textarea'],
                    'landing.note' => ['label' => 'הערת פרטיות (מודגש)', 'type' => 'text'],
                    'landing.form_title' => ['label' => 'כותרת טופס הלידים', 'type' => 'text'],
                    'landing.form_text' => ['label' => 'טקסט טופס הלידים', 'type' => 'text'],
                    'landing.signature' => ['label' => 'טקסט סיום', 'type' => 'textarea'],
                    'landing.signature_name' => ['label' => 'חתימה', 'type' => 'textarea'],
                ],
            ],
        ];
    }

    public function edit(string $group): View
    {
        $def = $this->groups()[$group];
        $values = [];
        foreach (array_keys($def['fields']) as $key) {
            $values[$key] = $this->settings->isSecret($key) ? '' : $this->settings->get($key);
        }
        $hasSecret = [];
        foreach (array_keys($def['fields']) as $key) {
            if ($this->settings->isSecret($key)) {
                $hasSecret[$key] = (bool) $this->settings->get($key);
            }
        }

        return view('admin.settings', [
            'group' => $group,
            'def' => $def,
            'values' => $values,
            'hasSecret' => $hasSecret,
            'groups' => $this->groups(),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $def = $this->groups()[$group];
        $updates = [];

        foreach ($def['fields'] as $key => $field) {
            $input = str_replace('.', '__', $key);

            switch ($field['type']) {
                case 'toggle':
                    $updates[$key] = $request->boolean($input) ? '1' : '0';
                    break;
                case 'secret':
                    if ($request->boolean($input.'__clear')) {
                        $updates[$key] = '';
                    } elseif ($request->filled($input)) {
                        $updates[$key] = trim((string) $request->input($input));
                    }
                    break;
                case 'image':
                    if ($request->boolean($input.'__remove')) {
                        $this->images->delete([$this->settings->get($key)]);
                        $updates[$key] = '';
                    } elseif ($request->hasFile($input)) {
                        $request->validate([$input => ['file', 'mimes:svg,png,jpg,jpeg,webp', 'max:4096']]);
                        $this->images->delete([$this->settings->get($key)]);
                        $updates[$key] = $this->images->storeRaw($request->file($input), 'settings');
                    }
                    break;
                case 'features':
                    $items = [];
                    foreach ((array) $request->input($input, []) as $row) {
                        if (filled($row['text'] ?? null)) {
                            $items[] = ['icon' => $row['icon'] ?? 'dove', 'text' => trim($row['text'])];
                        }
                    }
                    $updates[$key] = json_encode($items, JSON_UNESCAPED_UNICODE);
                    break;
                case 'richtext':
                    $updates[$key] = app(\App\Services\Html\HtmlSanitizer::class)->clean($request->input($input));
                    break;
                default:
                    $updates[$key] = trim((string) $request->input($input, ''));
            }
        }

        if ($group === 'mail') {
            $request->validate(['mail__port' => ['nullable', 'integer', 'between:1,65535'], 'mail__from_address' => ['nullable', 'email']]);
        }

        $this->settings->setMany($updates);
        app(DynamicMailConfigurator::class)->apply();

        return redirect()->route('admin.settings.edit', $group)->with('status', 'ההגדרות נשמרו.');
    }

    public function testMail(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => ['required', 'email']], [], ['test_email' => 'כתובת לבדיקה']);
        app(DynamicMailConfigurator::class)->apply();

        try {
            Mail::mailer($this->settings->mailConfigured() ? 'smtp' : config('mail.default'))
                ->to($request->input('test_email'))
                ->send(new TestMail);
        } catch (Throwable $e) {
            return back()->withErrors(['test' => 'שליחת המייל נכשלה: '.$e->getMessage()]);
        }

        $note = $this->settings->mailConfigured() ? '' : ' (SMTP כבוי — המייל נכתב ללוג בלבד)';

        return back()->with('status', 'מייל הבדיקה נשלח אל '.$request->input('test_email').$note);
    }

    public function testSms(Request $request, SmsManager $sms): RedirectResponse
    {
        $request->validate(['test_phone' => ['required', 'string']], [], ['test_phone' => 'מספר לבדיקה']);
        $phone = PhoneNumber::normalize($request->input('test_phone'));
        if (! $phone) {
            return back()->withErrors(['test' => 'מספר הטלפון אינו תקין.']);
        }

        $result = $sms->send($phone, 'הודעת בדיקה מ-'.config('endless.brand.name', 'Endless').' — מערכת ה-SMS מוגדרת כראוי.');
        if (! $result->ok) {
            return back()->withErrors(['test' => $result->message]);
        }

        $note = $sms->isConfigured() ? '' : ' (SMS כבוי — ההודעה נכתבה ללוג בלבד)';

        return back()->with('status', 'הודעת הבדיקה נשלחה אל '.PhoneNumber::format($phone).$note);
    }
}
