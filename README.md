# Endless — מערכת עמודי הנצחה

מערכת Laravel ליצירת עמודי הנצחה אישיים, איסוף זיכרונות מבני משפחה וחברים, וניהול הכל מאזור אישי בעברית.

---

## מה יש במערכת

| חלק | כתובת | תיאור |
|---|---|---|
| עמוד הבית | `/` | שכפול 1:1 של דף הבית ב-endless.day, בעברית ובפונט Heebo: Hero במסך מלא, שלוש הבטחות, "איך זה עובד" עם כרטיסים נערמים, "למה Endless", המלצות ו-CTA |
| חייל השריון | `/shiryon` | עמוד נחיתה בסגנון "יד לשריון": כרטיס לבן, ארבע תכונות, "איך זה עובד", טופס לידים |
| יצירת עמוד | `/register` | טופס הרשמה מלא (פרטי הנפטר + פרטי היוצר), תמונות, ואימות בקוד חד־פעמי |
| כניסה | `/login` | קוד חד־פעמי ב‑SMS או במייל, ללא סיסמה |
| עמוד הנצחה | `/m/{slug}` | Hero, ביוגרפיה, פיד זיכרונות (masonry + טעינה נוספת), גלריה, ציטוט, "הוקם ע״י" |
| זיכרון בודד | `/m/{slug}/memory/{id}` | כרטיס קריאה + שאר הזיכרונות |
| העלאת זיכרון | `/m/{slug}/share/{token}` | טופס ציבורי עם עורך תוכן עשיר והעלאת תמונות |
| אזור אישי | `/dashboard` | סקירה, עריכת העמוד, ניהול זיכרונות, ספר, שיתוף, חשבון |
| ספר הזיכרונות | `/dashboard/book` | בחירת תוכן וגודל, הדגמת ספר מדף לדף, ומספר עותקים (ההזמנה עדיין סגורה) |
| ניהול | `/admin` | הגדרות SMTP / 019SMS, עמודי הנצחה, משתמשים, פניות, תוכן עמוד חייל השריון |

---

## התקנה

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

לאחר ה‑seed קיימים:

- **מנהל** — הכתובת שב‑`ADMIN_EMAIL` (ברירת מחדל `admin@example.com`)
- **עמוד דמו** — `/m/kochav`, בעלים `demo@endless.test` / `+972500000001`

בפיתוח מוגדר `OTP_DEV_CODE=123456` — כל קוד אימות שווה לערך הזה ומוצג בבאנר במסך האימות. בייצור משאירים את המשתנה ריק.

### משתני סביבה עיקריים

```
APP_URL=http://127.0.0.1:8000       # חשוב לקישורי שיתוף ולמיילים
DB_CONNECTION=sqlite                 # או mysql בייצור
QUEUE_CONNECTION=sync                # database + worker בייצור
ADMIN_EMAIL=you@example.com
OTP_DEV_CODE=                        # ריק בייצור
FILESYSTEM_PUBLIC_URL=/storage       # כתובת יחסית לקבצים
```

---

## הגדרת שליחת הודעות

הכל נעשה מהממשק, בלי לגעת ב‑`.env`.

### SMTP — `/admin/settings/mail`

שרת, פורט, הצפנה (TLS/SSL), שם משתמש, סיסמה, כתובת ושם שולח.
הסיסמה נשמרת **מוצפנת** בבסיס הנתונים. כפתור "שליחת מייל בדיקה" שולח הודעה אמיתית.
כל עוד המתג כבוי — המיילים נכתבים ל‑`storage/logs/laravel.log` בלבד.

### 019SMS — `/admin/settings/sms`

שם משתמש, API Token (Bearer) או סיסמה בחשבונות ישנים, ושם/מספר שולח מאושר.
הבקשה נשלחת כ‑JSON ל‑`https://019sms.co.il/api`:

```json
{ "sms": { "user": { "username": "..." }, "source": "Endless",
           "destinations": { "phone": [ { "_": "0501234567" } ] },
           "message": "..." } }
```

`status: 0` בתשובה = ההודעה התקבלה. כפתור "שליחת SMS בדיקה" שולח הודעה אמיתית.

### ערוץ קוד האימות

`otp.preferred_channel` קובע: אוטומטי (SMS אם מוגדר, אחרת מייל), SMS בלבד, או מייל בלבד.
אם ערוץ אחד נכשל — המערכת מנסה את השני. במסך האימות יש כפתור מעבר ידני בין הערוצים.

---

## החלטות ארכיטקטורה

- **Laravel 13 + Blade + Alpine.js.** ללא SPA — מהיר, נגיש, ו‑RTL פשוט.
- **CSS מודולרי** (`resources/css/tokens.css` · `base.css` · `components.css`) עם Custom Properties. הטוקנים נמדדו מעמודי הרפרנס ב‑`endless.day`; הפונט היחיד הוא **Heebo**.
- **Masonry ב‑JS** (`resources/js/components/masonry.js`) ולא `columns` של CSS, כדי שהכרטיסים ימולאו שורה‑שורה בדיוק כמו ברפרנס.
- **OTP במקום סיסמאות.** הקודים נשמרים כ‑hash, תוקף 10 דקות, 5 ניסיונות, הגבלת קצב לפי מזהה ולפי IP.
- **סניטציה של HTML** ברשימת היתר (`app/Services/Html/HtmlSanitizer.php`) על כל תוכן מהמשתמשים.
- **תמונות** עוברות ל‑WebP עם תמונה ממוזערת (Intervention Image), ונשמרות ב‑`storage/app/public`.
- **פרטיות:** עמודי הנצחה הם `noindex,nofollow`, נגישים בקישור בלבד, והזיכרונות דורשים אישור הבעלים כברירת מחדל.

---

## בדיקות

```bash
php artisan test
```

79 בדיקות מכסות הרשמה, כניסה ב‑OTP, הגשת זיכרונות ומודרציה, האזור האישי, ההרשאות, ההגדרות, מבנה בקשת 019SMS, והסניטייזר.

---

## פריסה לייצור

1. `DB_CONNECTION=mysql`, `APP_ENV=production`, `APP_DEBUG=false`, `OTP_DEV_CODE=` ריק.
2. `APP_URL` לדומיין האמיתי — ממנו נבנים קישורי השיתוף והמיילים.
3. `QUEUE_CONNECTION=database` + `php artisan queue:work` (מיילים והתראות נשלחים ברקע).
4. `php artisan migrate --force && npm run build && php artisan storage:link`
5. `php artisan config:cache route:cache view:cache`
6. להגדיר SMTP ו‑019SMS מהממשק, ולשלוח בדיקה לכל אחד.

---

## גישה לאתר הוורדפרס (אופציונלי)

`tools/wp-bridge/endless-ai-bridge.php` — מודול קריאה‑בלבד להוספה ל‑`functions.php` של התבנית ב‑`endless.day`.
הוא פותח נקודות קצה מוגנות‑טוקן לקריאת סכמה, שדות ACF וקבצי תבנית/תוסף, לצורך השוואה מדויקת לרפרנס.
הטוקן נוצר ב‑**Tools → AI Bridge**, מוצג פעם אחת, ונשמר כ‑hash בלבד. אין בו שום פעולת כתיבה.
