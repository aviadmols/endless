# Endless — מערכת דפי הנצחה (Laravel) · תוכנית עבודה

> מסמך זה נכתב לפני תחילת הפיתוח ומשמש כמפרט + תוכנית ביצוע.
> הרפרנס העיצובי: `endless.day` (עמוד יד לשריון, עמוד הנצחה "אברהם כוכב", פיד `/ko/`, טופס `/join/`, `/signin/`, עמוד זיכרון בודד).
> הפונט היחיד במערכת: **Heebo** (במקום NarkissBlock / Circular / הפונט הסריפי של הרפרנס).

---

## 1. מטרות המערכת

1. **יצירת דפי הנצחה** — משפחה נרשמת (טופס בסגנון `/join/`), נפתח לה משתמש, ונוצר עמוד הנצחה פרטי (לא מאונדקס) בעיצוב עמוד "אברהם כוכב".
2. **מנגנון זיכרונות** — כל מי שקיבל קישור יכול להעלות זיכרון (שם + תמונות + תוכן עשיר). הזיכרונות מוצגים בפיד נטען (Load more) עם כפתור "העלו זיכרון" קבוע, בסגנון `/ko/`.
3. **אזור אישי בעברית מלאה** לבעל העמוד — עריכת פרופיל ההנצחה, צפייה/אישור בכל הזיכרונות, שיתוף קישור לטופס העלאת זיכרון.
4. **כניסה באמצעות קוד אימות (OTP)** ב‑SMS (019SMS) או במייל (SMTP) — ללא סיסמאות.
5. **פאנל ניהול** למנהל המערכת: הגדרות SMTP, הגדרות חשבון 019SMS API, ניהול דפי הנצחה ומשתמשים, עריכת תוכן עמוד הבית.

---

## 2. מה נלמד מהרפרנס (מפרט עיצוב)

### 2.1 טוקנים גלובליים (נמדדו מהאתר החי)

| טוקן | ערך ברפרנס | אצלנו |
|---|---|---|
| רקע עמוד | `#F9F8F5` | `--paper-warm: #F9F8F5` |
| רקע כרטיס/לבן | `#FFFFFF` | `--paper: #FFFFFF` |
| טקסט ראשי | `#1D1D20` | `--ink: #1D1D20` |
| טקסט משני | `#575757` / `#333333` | `--ink-soft: #575757` |
| קו/גבול שדות | `#DDDDDD` | `--rule: #DDDDDD` |
| רקע אפור בהיר (טופס לידים) | `#F3F1F1` | `--soft: #F3F1F1` |
| אקסנט כפתור פיד | `#EAB09A` (סלמון) | `--accent-feed: #EAB09A` |
| ירוק אייקונים (יד לשריון) | ירוק line-icons | `--accent-green: #2F7D3A` |
| רדיוס כרטיס גדול | `20px` | `--r-card: 20px` |
| רדיוס תמונות / כרטיסי זיכרון | `12px` | `--r-img: 12px` |
| רדיוס כפתורים | `12px` | `--r-btn: 12px` |
| רדיוס שדות טופס | `10px` | `--r-input: 10px` |
| רוחב תוכן | `1200px` (עמוד הנצחה) / `800px` (טופס) / `777px` (ביו/ציטוט) / `700px` (זיכרון בודד) | כנ"ל |
| ריווח סקשן | `75px 0` | `--section-y: 75px` |
| מעבר | `0.3–0.5s ease` | `--t: 0.5s ease` |
| פונט | NarkissBlock + Circular | **Heebo** בלבד |

### 2.2 טיפוגרפיה (Heebo)

| שימוש | גודל | משקל | letter-spacing |
|---|---|---|---|
| כותרת‑על ("לזכרו של יקירנו") | 18px | 400 | 3px |
| שם הנפטר (hero) | 55px (44 במובייל) | 400 | 1px |
| תאריכים | 18px | 400 | 3px |
| כותרת סקשן (21px ברפרנס) | 21px | 400 | 3px |
| ביוגרפיה / טקסט זיכרון | 19px, line‑height 1.5 | 300 | 0 |
| ציטוט | 40px, line‑height 1.4 (28px במובייל) | 200 | 0 |
| שם המצטט / "הוקם ע״י" | 15–17px | 400 | 1px |
| כפתור | 17px | 400–500 | 0 |
| תווית שדה (floating) | 12px | 400 | 0 |
| שדה טופס | 17px | 400 | 0 |
| כותרת טופס ("Create"/"Sign in") | 50–55px | 300 (Light) | 0 |
| פוטר | 14px | 400 | 0 |

### 2.3 עמודים ורכיבים (מבנה מדויק)

**Header (קבוע, 80px, שקוף):** לוגו ENDLESS (SVG/טקסט Heebo 300 עם ריווח אותיות) בצד ההתחלה; תפריט + כפתור outline "צור עמוד" + burger בצד השני. במובייל: לוגו + burger.

**עמוד הבית (סגנון "יד לשריון"):**
1. רקע `#F9F8F5` עם תמונת רקע ענקית מעומעמת בתחתית.
2. כרטיס לבן מרכזי 800px, radius 20px, padding 50px: לוגואי שותפים (grayscale), קו מפריד, כותרת H4 24px, פסקאות 19px, שורת 4 אייקונים ירוקים עם כיתוב (feature strip), תמונה 12px radius, "איך זה עובד" + רשימת bullets, הערת פרטיות, בלוק אפור `#F3F1F1` עם טופס לידים בשורה אחת (שם / דוא"ל / טלפון / כפתור שחור), חתימה "צוות Endless".
3. פוטר לבן 76px: זכויות + קישורים.

**עמוד הנצחה (סגנון "אברהם כוכב"):**
1. Hero 100vh: וידאו רקע (או תמונה) + overlay גרדיאנט `rgba(249,248,245,.77) → #F9F8F5`; במרכז: אייקון דת קטן (20px), "לזכרו של יקירנו", שם 55px, תאריכים, ואז וידאו/תמונת פורטרט 300×418 (object-fit cover).
2. סקשן ביוגרפיה: 777px, כותרת (Biography‑title) 21px ls 3px, פסקאות 19px/300 ממורכזות.
3. סקשן זיכרונות: כותרת "זיכרונך חי בכל מה שאנחנו עושים" + **פיד**: (א) בדסקטופ — masonry 3 עמודות, כרטיס 12px radius, תמונה + גרדיאנט `transparent 35% → #1D1D20`, טקסט לבן 19px במרכאות, שם המעלה 16px ls 1px; (ב) במובייל — פיד `/ko/`: כרטיסים 6px radius בצל עדין, תמונה 320px עם gradient‑fade לבן + snippet, לחיצה פותחת את הטקסט המלא + "שם • תאריך", כרטיסי טקסט‑בלבד, כרטיסי גלריה אופקית. כפתור "טען עוד" + כפתור CTA קבוע בתחתית "העלו זיכרון" (`#EAB09A`).
4. גלריה "רגעים של אהבה": masonry 4 עמודות (275px), 12px radius, lightbox.
5. ציטוט: 777px, 40px/200, שם המצטט 15px.
6. "הוקם ע״י …" 17px ls 1px על לבן.
7. סרגל דביק (mobile) "שיתוף" (outline) + "הוסיפו זיכרון" (filled).
8. `<meta name="robots" content="noindex,nofollow">` — עמוד פרטי.

**עמוד זיכרון בודד (`/memories/{slug}`):** כרטיס לבן 700px: תמונה עליונה radius 12px למעלה, שורת "חזרה לפרופיל" / "מאת: שם", תוכן 19px/300 מיושר לימין, כפתור outline "חזרה לפרופיל", ואז masonry של שאר הזיכרונות.

**טופס הרשמה (`/join` → אצלנו `/register`), סגנון מדויק:** רקע `#F9F8F5` + תמונה קבועה בחצי הימני/שמאלי (fixed, hidden במובייל); כרטיס לבן 800px, padding 50px, radius 20px; כותרת 50px Light, תת‑כותרת 17px; שדות עם **תווית צפה** (label 12px על הגבול), גבול 1px `#DDD`, radius 10px, padding 8px 15px, גובה 43px; שורות של 2 שדות; קבוצת רדיו "זכר/נקבה" כמקטע מחולק; select "דת"; תיבת "העלאת תמונות" (150px, אייקון + טקסט + הערת פורמטים + תצוגה מקדימה עם גרירה לסידור ומחיקה); textarea "כמה מילים על האדם…" 150px; הערה; `<hr>`; "הפרטים שלך": שם פרטי/משפחה, אימייל, קידומת מדינה + טלפון; הערה על קוד אימות; צ'קבוקס תנאים (custom 18px radius 5px); כפתור שחור מלא 57px radius 12px "יצירת עמוד הנצחה".

**טופס העלאת זיכרון (עיצוב זהה לטופס ההרשמה):** שם המעלה*, (טלפון/אימייל אופציונלי לעדכון), תיבת העלאת תמונות (מרובות, תצוגה מקדימה, סידור), **עורך תוכן עשיר** (Quill: bold/italic/underline/כותרות/ציטוט/רשימות/קישור, RTL), כפתור "שמור זיכרון". נפתח גם כ‑sheet מהפיד ("זיכרון חדש" עם X).

**כניסה (`/signin` → אצלנו `/login`):** חצי מסך תמונה (דיונה בשחור‑לבן) + חצי טופס: "כניסה" 55px Light, הסבר, שדה טלפון/אימייל (זיהוי אוטומטי), כפתור שחור "שלחו לי קוד", שלב 2: שדה קוד 6 ספרות + "כניסה" / "שליחה חוזרת" + טיימר.

**אזור אישי (Dashboard) — עברית מלאה, RTL:** sidebar/טאבים: סקירה · עריכת העמוד · זיכרונות · שיתוף · החשבון שלי. עיצוב כרטיסים לבנים 20px radius על `#F9F8F5`, אקורדיון מקטעים לעריכה (כמו הפלאגין), טאבים ממתינים/מאושרים/נדחו, כפתורי אישור/דחייה/מחיקה, העתקת קישור + שיתוף בוואטסאפ + QR.

---

## 3. ארכיטקטורה טכנית

| רכיב | בחירה | נימוק |
|---|---|---|
| Framework | **Laravel 13** (PHP 8.4, Herd) | סטנדרט הפרויקטים הקיימים |
| DB | SQLite לפיתוח, MySQL‑ready (migrations ניטרליות) | פשטות + ייצור |
| Views | Blade + **Alpine.js** | ללא SPA, מהיר, נגיש, RTL קל |
| CSS | Vite + CSS מודולרי עם custom properties (ללא Tailwind) | שליטה מלאה בטוקנים של העיצוב |
| פונט | Heebo מ‑Google Fonts (100–900) + fallback | דרישת הלקוח |
| עורך עשיר | **Quill 2** (RTL, toolbar מותאם) + sanitizer צד‑שרת (allowlist) | חופשי, יציב |
| תמונות | **Intervention Image v3** (GD) → resize + WebP + thumbnails, אחסון `storage/app/public` | ללא תלות ב‑CDN |
| Auth | מנגנון OTP מותאם (ללא Breeze): `otp_codes` + session | דרישת הלקוח |
| SMS | Driver `019sms` (HTTPS JSON API) + driver `log` לפיתוח | דרישת הלקוח |
| Mail | SMTP דינמי מה‑DB (Settings) + `log` לפיתוח | דרישת הלקוח |
| Settings | טבלת `settings` (key/value, ערכים רגישים מוצפנים) + cache | ניהול מהאדמין |
| Queue | `database` — שליחת מיילים/SMS ועיבוד תמונות ברקע (עם fallback sync) | חוויית משתמש |
| בדיקות | PHPUnit/Pest Feature tests | איכות |

---

## 4. מודל נתונים

**users** — id, first_name, last_name, email (unique, nullable), phone (E.164, unique, nullable), is_admin, locale=he, last_login_at, timestamps.

**otp_codes** — id, user_id (nullable — לרישום לפני יצירת משתמש), identifier (email/phone), channel (`sms|email`), code_hash, expires_at (10 דק'), attempts, consumed_at, ip, timestamps. אינדקסים על identifier+created_at.

**memorials** — id, user_id (owner), slug (unique, לטיני/עברי), first_name, last_name, gender (`male|female`), subtitle ("לזכרו של יקירנו" ברירת מחדל לפי מגדר), birth_date, death_date, dates_text (טקסט חופשי חלופי, כמו שדה `dates`), hebrew_dates (אופציונלי), religion (`jewish|christian|muslim|druze|other|none`), religion_icon_path (או מהסט המובנה), video_url (URL — שדה `video`), hero_video_path / hero_image_path (רקע), portrait_video_path / portrait_image_path, biography_title, biography (HTML), quote, quote_name, founder_name ("הוקם ע״י"), share_token (uuid — לקישור טופס הזיכרון), visibility (`private|unlisted`), require_approval (bool, ברירת מחדל true), views_count, published_at, timestamps, softDeletes.

**memorial_images** — id, memorial_id, path, thumb_path, width, height, alt, sort_order, timestamps. (הגלריה "רגעים של אהבה" — שדה `gallery`).

**memories** — id, memorial_id, author_name, author_email (nullable), author_phone (nullable), title (nullable), body (HTML מסונן), body_plain (לתקציר/חיפוש), status (`pending|approved|rejected`), approved_at, ip, user_agent, submitted_via (`link|owner`), sort/created_at, softDeletes.

**memory_images** — id, memory_id, path, thumb_path, width, height, sort_order.

**settings** — key (PK), value (text; JSON/מוצפן לפי צורך), updated_at. מפתחות: `mail.*` (host, port, username, password🔒, encryption, from_address, from_name), `sms.provider`, `sms.019.username`, `sms.019.token`🔒 (או password), `sms.019.source`, `general.site_name`, `general.contact_email`, `landing.*` (טקסטים/תמונות עמוד הבית), `otp.channel_default`.

**leads** — id, name, email, phone, source_page, timestamps (טופס "אנחנו מחכים לכם").

**activity_log** (קל) — id, user_id, memorial_id, action, meta JSON, timestamps — לביקורת אישורים/שינויים.

---

## 5. מפת ראוטים

### ציבורי
- `GET /` — עמוד הבית (סגנון יד לשריון) · `POST /leads`
- `GET /register` — טופס יצירת עמוד (סגנון join) · `POST /register` → יוצר user+memorial, שולח OTP → `GET/POST /register/verify`
- `GET /login` · `POST /login/send-code` · `POST /login/verify` · `POST /logout`
- `GET /m/{slug}` — עמוד ההנצחה (noindex) · `GET /m/{slug}/memories?page=` (JSON/HTML partial ל‑load more)
- `GET /m/{slug}/memories/{memory}` — זיכרון בודד
- `GET /m/{slug}/share/{token}` — טופס העלאת זיכרון (הקישור לשיתוף) · `POST /m/{slug}/share/{token}`
- `GET /m/{slug}/gallery` (JSON ל‑lightbox, אופציונלי)

### אזור אישי (auth)
- `GET /dashboard` — סקירה
- `GET /dashboard/memorial` · `PUT /dashboard/memorial` — עריכת כל שדות ההנצחה (מקטעים)
- `POST /dashboard/memorial/images` · `DELETE …/{image}` · `PATCH …/reorder`
- `POST /dashboard/memorial/media` (hero/portrait video+image)
- `GET /dashboard/memories?status=` · `PATCH /dashboard/memories/{id}/approve|reject` · `DELETE …` · `GET/PUT …/edit`
- `GET /dashboard/share` — קישורים, QR, וואטסאפ · `POST /dashboard/share/regenerate`
- `GET/PUT /dashboard/account`

### אדמין (auth + is_admin)
- `GET /admin` — סקירה
- `GET/PUT /admin/settings/mail` (+ `POST …/test`) · `GET/PUT /admin/settings/sms` (+ `POST …/test`) · `GET/PUT /admin/settings/general` · `GET/PUT /admin/settings/landing`
- `GET /admin/memorials` · `GET/PUT /admin/memorials/{id}` · `POST …/login-as`
- `GET /admin/users` · `GET /admin/leads`

---

## 6. זרימות מרכזיות

**הרשמה:** מילוי טופס → ולידציה → יצירת user (אם האימייל/טלפון קיימים: הודעה + הפניה לכניסה) → יצירת memorial + slug → העלאת תמונות (queue: resize/WebP) → שליחת OTP (SMS אם יש טלפון ו‑SMS מוגדר, אחרת מייל) → מסך אימות → session → `/dashboard` עם הודעת "העמוד נוצר!" + קישור.

**כניסה:** קלט אחד (אימייל או טלפון) → זיהוי → OTP 6 ספרות (hash, תוקף 10 דק', 5 ניסיונות, rate limit 3 שליחות/10 דק') → אימות → session (remember 30 יום).

**העלאת זיכרון:** דרך קישור השיתוף (token) → טופס → sanitization של HTML → תמונות (עד 10, 8MB כ"א, jpg/png/webp/gif) → סטטוס `pending` (או `approved` אם הבעלים כיבה אישור) → הודעה למשתמש "הזיכרון נשלח לאישור" → התראת מייל/SMS לבעלים (queue).

**פיד:** `GET /m/{slug}/memories?after=` מחזיר partial HTML (8 בכל טעינה) — Alpine מוסיף ל‑DOM, IntersectionObserver ל‑fade‑in.

**SMS 019:** `POST https://019sms.co.il/api` (JSON) עם `Authorization: Bearer {token}`; גוף: `{"sms":{"user":{"username":…},"source":…,"destinations":{"phone":[{"_":…}]},"message":…}}`; `status==0` = הצלחה. נעטוף ב‑`SmsManager` עם drivers: `019sms`, `log`, ולוג שגיאות. כפתור "שלח SMS בדיקה" באדמין.

**SMTP:** `MailSettingsProvider` טוען את ההגדרות מה‑DB בזמן ריצה (`config()->set('mail.mailers.smtp', …)`); אם ריק → `log`. כפתור "שלח מייל בדיקה".

---

## 7. אבטחה ופרטיות

- עמודי הנצחה: `noindex,nofollow`, ללא sitemap, ללא רשימה ציבורית. `visibility=private` = דורש קישור עם slug בלבד (כמו ברפרנס); אפשרות לבעלים לשנות.
- קישור העלאת זיכרון עם `share_token` (ניתן לחידוש).
- OTP: hash (`Hash::make`), תוקף, ניסיונות, throttle לפי IP + identifier, ללא חשיפה האם משתמש קיים.
- HTML של זיכרונות/ביוגרפיה: sanitizer allowlist (p, br, strong, em, u, s, h2, h3, blockquote, ul, ol, li, a[href|rel|target]).
- קבצים: ולידציית MIME אמיתית (finfo), הגבלת גודל, שמות אקראיים, אחסון מחוץ ל‑public + symlink.
- CSRF בכל טופס, Policies (`MemorialPolicy`, `MemoryPolicy`), Gate `admin`.
- ערכים רגישים ב‑settings מוצפנים (`Crypt`).

---

## 8. שלבי ביצוע (Phases) וקריטריוני קבלה

| # | שלב | תוצרים | קריטריון קבלה |
|---|---|---|---|
| 0 | Scaffold | Laravel 13, Vite, Alpine, Quill, Heebo, `design/tokens.css`, layout ראשי, `lang/he` | `php artisan serve` מציג layout עם הפונט |
| 1 | DB | migrations, models, relations, factories, seeder דמו ("אברהם כוכב" עם 10 זיכרונות וגלריה) | `migrate --seed` עובר |
| 2 | Settings + Mail + SMS | טבלת settings, `SettingsRepository`, `SmsManager` (019/log), `DynamicMailConfig`, מסכי אדמין + בדיקה | שליחת OTP ללוג עובדת; מבנה בקשת 019 מכוסה בטסט |
| 3 | Auth OTP | login/register/verify, middleware, throttle, remember | טסטים: send→verify→dashboard |
| 4 | עמודים ציבוריים | Home, memorial page (hero/bio/feed/gallery/quote/founder/sticky bar), memory single, share form + Quill + uploads, feed load‑more + sheet | השוואה ויזואלית מול צילומי הרפרנס (Playwright) |
| 5 | אזור אישי | סקירה, עריכת עמוד (אקורדיון), מדיה, זיכרונות (טאבים+מודרציה), שיתוף (copy/WA/QR), חשבון | כל פעולה עם feedback בעברית |
| 6 | אדמין | הגדרות (mail/sms/general/landing), memorials, users, leads, login‑as | הגדרות נשמרות ומוצפנות |
| 7 | ליטוש | responsive (400/768/1024/1440), RTL, נגישות (focus, aria), אנימציות fade, 404/419/500 בעיצוב | בדיקת Playwright במובייל ודסקטופ |
| 8 | בדיקות ותיעוד | Feature tests (auth, registration, memories, moderation, settings, sanitizer), README (התקנה, .env, 019, SMTP, deploy) | `php artisan test` ירוק |

---

## 9. הנחות והחלטות (ניתן לשנות)

1. **Heebo** מחליף את כל שלושת הפונטים של הרפרנס; הגדלים/המשקלים נשמרים, כותרות‑ענק (Create/Sign in) ב‑Heebo Light 300.
2. **פינות מעוגלות** נשמרות כמו ברפרנס (20/12/10px) — זו דרישה מפורשת של "בדיוק כמו", ולכן היא גוברת על כלל הפינות החדות של שפת העיצוב הרגילה.
3. **שדות ה‑ACF** מהצילום ממופים כך: religion→בחירה מסט אייקונים (+העלאה מותאמת), video→URL/קובץ, dates→birth/death + טקסט חופשי, Biography‑title/Biography/gallery/quote/quote‑name → 1:1.
4. **פרטיות ברירת מחדל:** עמוד פרטי (noindex), זיכרונות דורשים אישור הבעלים.
5. **ווידאו רקע ה‑hero** — קובץ ברירת מחדל מקומי ניטרלי (עננים/רקע רך) + אפשרות העלאה; לא נעתיק מדיה מ‑endless.day.
6. **019SMS** — מימוש לפי ה‑API הציבורי של 019 (JSON + Bearer token). אם החשבון שלך עובד עם username/password בלבד, הדרייבר תומך גם בזה (אימות בסיסי בגוף הבקשה). נאמת מול החשבון האמיתי כשתזין פרטים.
7. **אחסון מדיה** מקומי (`storage/app/public`). מעבר ל‑S3/Cloudinary אפשרי דרך `FILESYSTEM_DISK`.
8. **סביבת פיתוח:** Herd PHP 8.4 + SQLite; ייצור: MySQL 8 + Nginx/Forge.

---

## 10. גישה ל‑WordPress (אופציונלי)

קובץ `tools/wp-bridge/endless-ai-bridge.php` — מודול read‑only שמתווסף ל‑`functions.php` של התבנית ב‑endless.day ומאפשר לי (עם טוקן) לקרוא סכמה/שאילתות/שדות ACF/קבצי הפלאגין. משמש רק כדי לדייק פרטים (למשל מבנה הדשבורד של הפלאגין) — לא חובה להשלמת המערכת.
