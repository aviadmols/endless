<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\MemoryStatus;
use App\Enums\Religion;
use App\Models\Memorial;
use App\Models\User;
use Database\Seeders\Support\DemoImages;
use Illuminate\Database\Seeder;

/**
 * A complete demo memorial ("אברהם כוכב") with biography, gallery, quote and a feed of memories,
 * so the design can be reviewed right after `migrate --seed`. Images are generated locally (no external media).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Memorial::where('slug', 'kochav')->exists()) {
            return;
        }

        $owner = User::query()->updateOrCreate(
            ['email' => 'demo@endless.test'],
            ['first_name' => 'אור', 'last_name' => 'כוכב', 'phone' => '+972500000001', 'email_verified_at' => now(), 'phone_verified_at' => now()]
        );

        $images = new DemoImages;

        $memorial = Memorial::create([
            'user_id' => $owner->id,
            'slug' => 'kochav',
            'first_name' => 'אברהם',
            'last_name' => 'כוכב',
            'gender' => Gender::Male,
            'subtitle' => 'לזכרו של יקירנו',
            'birth_date' => '1951-06-19',
            'death_date' => '2022-08-15',
            'religion' => Religion::Jewish,
            'biography_title' => 'בעל, אב, סב, אח, איש משפחה וגיבור',
            'biography' => '<p>זהו דף זיכרון המוקדש לחייו ולמורשתו של אברהם כוכב – אדם שחי מתוך עוצמה, חמלה ואהבה בלתי מתפשרת לסובבים אותו.</p>'
                .'<p>הוא היה הרבה מעבר לאבא. הוא היה מגדלור המאיר את דרכנו, יד בוטחת ומכוונת, לב חם וכוח שקט בחיינו. כבעל, הוא היה מסור ונאמן. כסב, הוא מילא את חיינו בשמחה ורכות. כאח, הוא הביא עמו נאמנות וצחוק. כאיש משפחה, הוא נתן את כל כולו – ויותר מכך. עבורנו, הוא היה, ותמיד יהיה, גיבור.</p>'
                .'<p>לא עובר יום שבו איננו חשים את הכאב שבחסרונך. אנו מתגעגעים לקולך, לחוכמתך, לחיוך שלך – ולדרך שבה גרמת להכל להרגיש שיהיה בסדר. אולי הלכת מעימנו, אך לעולם לא תצא מליבנו. אנו מתגעגעים אליך בכל יום ויום. זכרך ממשיך לחיות בכל אשר נעשה.</p>',
            'quote' => '“הדבר החשוב ביותר שלמדתי הוא לא איך לנצח, אלא איך להישאר אדם טוב גם כשלא.”',
            'quote_name' => 'אברהם כוכב',
            'founder_name' => 'אור כוכב',
            'visibility' => 'private',
            'require_approval' => true,
            'views_count' => 128,
        ]);

        $memorial->forceFill(['portrait_image_path' => $images->portrait("memorials/{$memorial->id}/portrait")])->save();

        // Gallery — a masonry-friendly mix of aspect ratios.
        $ratios = [[4, 3], [3, 4], [1, 1], [16, 9], [3, 4], [4, 3], [1, 1], [3, 4], [4, 3], [16, 9], [3, 4], [1, 1], [4, 3], [3, 4], [4, 3], [16, 9]];
        foreach ($ratios as $i => [$w, $h]) {
            $memorial->images()->create($images->photo("memorials/{$memorial->id}/gallery", $w, $h, $i) + ['sort_order' => $i]);
        }

        $memories = [
            ['יעל כוכב', 'אבא יקר,\nבתמונה הזאת אתה עומד לידי בסיום קורס מדריכות שריון. מחויך, גאה, עם המבט הזה שאומר בלי מילים: אני כאן, וזה מה שחשוב.\nידעתי שתבוא. לא היה בזה ספק. גם בתקופה עמוסה, גם אם הדרך רחוקה – אבא תמיד מגיע.\nגאה להיות הבת שלך.', [4, 3], '2024-10-12'],
            ['אור כוכב', 'שנתיים עברו ועדיין קשה לעכל ולקבל שאתה לא כאן איתנו. מדהים איך החוסר והגעגוע, שתמיד מלווים אותנו, רק מתעצמים עם הזמן. הטיול ההוא בצפון שבו לא הפסקנו לצחוק כל הדרך חזרה הביתה – היית תמיד אומר שהרגעים האלה הם מה שנשאר איתנו לנצח, והיום אני מבין כמה צדקת.', [4, 3], '2025-04-15'],
            ['מיטל כוכב', 'סבא שלי, בתמונה הזאת רואים את ההתחלה שלנו, את הרגע שבו נפגשנו לראשונה כשאני הייתי רק בת שנה. מאז, כל פעם שהמשפחה התאספה, היית המסמר של הערב.', [3, 4], '2025-01-20'],
            ['כוכב מלכה', 'שלוש שנים עברו מאז שהלכת לעולמך, ואתה חסר לי בכל דקה ודקה. בתמונה הזו, שבה שנינו צעירים, אני רואה את כל מה שהיה ואת כל מה שנשאר – אהבה פשוטה, עמוקה, יציבה.', [1, 1], '2025-02-04'],
            ['ארז לב רן', 'לזכרו של אמי פלנט ז״ל בלא מעט סיפורים קראתי טקסטים על כך שאני הוצאתי אותם לפועל. השקט שלך היה חזק יותר מכל מילה. תמיד ידעת מתי לשתוק ומתי לתת את העצה שתשנה את הכל.', null, '2025-05-10'],
            ['יצחק פופר', 'אמי פלנט (אברהם כוכב) נולד ב-16 במאי 1951. כיהן כקצין שריון ראשי, מפקד אוגדה בדרגת תת-אלוף ואיש עסקים. החיוך שלך שמאיר כל חדר שנכנסת אליו – אפילו ביום הכי מעונן ידעת להביא איתך את השמש.', [16, 9], '2024-12-18'],
            ['רוי קורן', 'לשיריון התגייסתי בגלל הספר עוז 77 של אביגדור קהלני. מי שקרא את התיאורים של קהלני על קרב עמק הבכא ועל דמותו של אמי, יודע שמדובר באדם נדיר.', null, '2025-03-14'],
            ['מוסיק אבן חן', 'אמי פלנט – המח״ט הבוס והחבר שהערצתי כבר לא איתנו. אתמול הלך לעולמו ברצוני לשתף בדברי ההספד שלי הערב. זכרונות מהימים הפשוטים שהיו לנו יחד, כמה אהבת את הטבע.', [4, 3], '2025-06-25'],
            ['אמנון כספי', 'ב-15.8.2022 הלך לעולמו תת-אלוף (מיל.) אמי פלנט. אמי פלנט היה מהגיבורים של מלחמת יום הכיפורים שבגבורתם ותושייתם בלמו את הכוחות הסוריים בגולן.', [3, 4], '2025-04-02'],
            ['אביגדור קהלני', 'השבוע נפטר חברי כאח לי אמי פלנט ולפי בקשתו הספדתי אותו. דמעות חנקו אותי והשעה הייתה לי קשה מנשוא. אמי היה מפקד מהמעלה הראשונה ואדם יקר.', [1, 1], '2025-07-08'],
            ['נועה', 'היום שבו חגגנו את יום ההולדת האחרון שלך. היית כל כך מאושר מוקף בכולנו. הרגע הזה חרוט לי בלב.', [3, 4], '2025-04-02'],
            ['תמר', 'שבת בבוקר, הקפה והעיתון. ככה תמיד אזכור אותך. היית עוגן של שקט בעולם הכל כך רועש הזה.', [4, 3], '2025-02-04'],
        ];

        foreach ($memories as $i => [$author, $text, $ratio, $date]) {
            $paragraphs = array_filter(array_map('trim', explode("\n", $text)));
            $memory = $memorial->memories()->create([
                'author_name' => $author,
                'body' => '<p>'.implode('</p><p>', array_map('e', $paragraphs)).'</p>',
                'body_plain' => implode("\n", $paragraphs),
                'status' => MemoryStatus::Approved,
                'approved_at' => $date.' 10:00:00',
                'submitted_via' => 'link',
                'created_at' => $date.' 10:00:00',
                'updated_at' => $date.' 10:00:00',
            ]);
            if ($ratio) {
                $memory->images()->create($images->photo("memorials/{$memorial->id}/memories", $ratio[0], $ratio[1], 20 + $i) + ['sort_order' => 0]);
            }
        }

        // A couple of pending memories so the moderation queue is visible in the dashboard.
        foreach ([['שירן', 'כל פעם שהמשפחה התאספה, היית המסמר של הערב. הצחוק שלך היה מדבק, והיכולת שלך לחבר בין כולם הייתה נדירה. הגעגועים לא מרפים.'], ['עידו', 'לימדת אותי שהדברים הקטנים הם אלה שבאמת חשובים. תודה על כל שיעורי החיים שהעברת לי בשקט האופייני לך.']] as $j => [$author, $text]) {
            $memory = $memorial->memories()->create([
                'author_name' => $author,
                'body' => '<p>'.e($text).'</p>',
                'body_plain' => $text,
                'status' => MemoryStatus::Pending,
                'submitted_via' => 'link',
            ]);
            if ($j === 0) {
                $memory->images()->create($images->photo("memorials/{$memorial->id}/memories", 4, 3, 40) + ['sort_order' => 0]);
            }
        }

        $this->command?->info('Demo memorial: /m/kochav (owner: demo@endless.test / +972500000001)');
    }
}
