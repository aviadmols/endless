<?php

return [
    'brand' => [
        'name' => env('ENDLESS_BRAND', 'Endless'),
    ],

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_seconds' => 45,
        'per_identifier_per_10m' => 4,
        'per_ip_per_10m' => 12,
        'dev_code' => env('OTP_DEV_CODE'), // local only: when set, every OTP equals this value
    ],

    'phone' => [
        'default_country' => '972',
        'countries' => [
            '972' => ['name' => 'ישראל', 'flag' => '🇮🇱', 'example' => '50-0000000'],
            '1' => ['name' => 'ארה״ב / קנדה', 'flag' => '🇺🇸', 'example' => '212-555-0100'],
            '44' => ['name' => 'בריטניה', 'flag' => '🇬🇧', 'example' => '7400 000000'],
            '33' => ['name' => 'צרפת', 'flag' => '🇫🇷', 'example' => '6 12 34 56 78'],
            '49' => ['name' => 'גרמניה', 'flag' => '🇩🇪', 'example' => '151 23456789'],
            '39' => ['name' => 'איטליה', 'flag' => '🇮🇹', 'example' => '312 345 6789'],
            '34' => ['name' => 'ספרד', 'flag' => '🇪🇸', 'example' => '612 34 56 78'],
            '31' => ['name' => 'הולנד', 'flag' => '🇳🇱', 'example' => '6 12345678'],
            '41' => ['name' => 'שווייץ', 'flag' => '🇨🇭', 'example' => '78 123 45 67'],
            '61' => ['name' => 'אוסטרליה', 'flag' => '🇦🇺', 'example' => '412 345 678'],
        ],
    ],

    'uploads' => [
        'image_max_kb' => 8192,
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'image_max_px' => 2000,
        'thumb_px' => 700,
        'webp_quality' => 82,
        'video_max_kb' => 61440,
        'video_mimes' => ['mp4', 'webm', 'mov'],
        'memory_max_images' => 10,
        'gallery_max_images' => 60,
    ],

    'feed' => [
        'per_page' => 8,
    ],

    'sms' => [
        // Drivers: 019sms | log
        'default' => env('SMS_DRIVER', 'log'),
        '019sms' => [
            'endpoint' => env('SMS_019_ENDPOINT', 'https://019sms.co.il/api'),
        ],
    ],

    /*
    | Settings schema. Keys are stored in the `settings` table and edited in the admin.
    | secret => stored encrypted.
    */
    'settings' => [
        'general.site_name' => ['default' => 'Endless', 'secret' => false],
        'general.contact_email' => ['default' => '', 'secret' => false],
        'general.notify_admin_on_register' => ['default' => '1', 'secret' => false],
        'general.logo_path' => ['default' => '', 'secret' => false],
        'general.side_image' => ['default' => '', 'secret' => false],

        'mail.enabled' => ['default' => '0', 'secret' => false],
        'mail.host' => ['default' => '', 'secret' => false],
        'mail.port' => ['default' => '587', 'secret' => false],
        'mail.encryption' => ['default' => 'tls', 'secret' => false], // tls | ssl | none
        'mail.username' => ['default' => '', 'secret' => false],
        'mail.password' => ['default' => '', 'secret' => true],
        'mail.from_address' => ['default' => '', 'secret' => false],
        'mail.from_name' => ['default' => 'Endless', 'secret' => false],

        'sms.enabled' => ['default' => '0', 'secret' => false],
        'sms.driver' => ['default' => '019sms', 'secret' => false],
        'sms.019.username' => ['default' => '', 'secret' => false],
        'sms.019.token' => ['default' => '', 'secret' => true],
        'sms.019.password' => ['default' => '', 'secret' => true],
        'sms.019.source' => ['default' => '', 'secret' => false],

        'otp.preferred_channel' => ['default' => 'auto', 'secret' => false], // auto | sms | email

        'landing.partner_logo_1' => ['default' => '', 'secret' => false],
        'landing.partner_logo_2' => ['default' => '', 'secret' => false],
        'landing.title' => ['default' => 'משפחה יקרה,', 'secret' => false],
        'landing.intro' => ['default' => "<p>אנחנו פונים אליכם ברגישות עמוקה ובכבוד גדול. זכר יקירכם מלווה את עשייתנו בכל צעד.</p>\n<p>ב‑<strong>Endless</strong> אנחנו מקימים עמודי זיכרון אישיים. המטרה היא ליצור בית דיגיטלי בטוח, מכובד ונגיש שבו תוכלו לשמר את המורשת, לאסוף זיכרונות, תמונות וסיפורים, ולתת מקום לקול של כל מי שהכיר ואהב.</p>", 'secret' => false],
        'landing.features' => ['default' => '[{"icon":"dove","text":"גלריית תמונות, סיפורים וזכרונות נצחיים"},{"icon":"heart","text":"אפשרות לבני משפחה וחברים להעלות זיכרונות"},{"icon":"cloud","text":"שימור דיגיטלי ארוך טווח, מסודר, מאובטח"},{"icon":"hand","text":"גישה מלאה למשפחות, ללא כל עלות"}]', 'secret' => false],
        'landing.image_path' => ['default' => '', 'secret' => false],
        'landing.how_title' => ['default' => 'איך זה עובד', 'secret' => false],
        'landing.how_intro' => ['default' => 'לאחר מילוי הפרטים בטופס, תקבלו קוד אימות ותועברו לאזור האישי של עמוד ההנצחה החדש.', 'secret' => false],
        'landing.how_list_title' => ['default' => 'דרך המערכת תוכלו:', 'secret' => false],
        'landing.how_list' => ['default' => "להשלים בקלות את הקמת העמוד (להוסיף תמונות, להעלות זכרונות ורגעים)\nלהעלות את הזיכרון הראשון ולאשר זיכרונות שיעלו עם הזמן\nלקבל לינק ייחודי שאותו תוכלו לשתף עם בני משפחה וחברים כדי שיעלו זיכרונות נוספים בצורה בטוחה.", 'secret' => false],
        'landing.note' => ['default' => '*שימו לב, העמוד הוא פרטי, אינו מופיע במנועי חיפוש ולא מתפרסם בו ללא אישורכם.', 'secret' => false],
        'landing.form_title' => ['default' => 'אנחנו מחכים לכם', 'secret' => false],
        'landing.form_text' => ['default' => 'מלאו את פרטיכם, ואנו נשלח אליכם בהמשך קישור מאובטח לעמוד ההנצחה', 'secret' => false],
        'landing.signature' => ['default' => "אם תרצו לשאול שאלות, להתייעץ או לקבל ליווי אישי,\nאנחנו זמינים עבורכם בכל עת וברגישות מלאה.", 'secret' => false],
        'landing.signature_name' => ['default' => "בכבוד רב,\nצוות Endless", 'secret' => false],
    ],
];
