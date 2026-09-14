@extends('layouts.dashboard')

@section('title', 'עריכת העמוד | ' . setting('general.site_name', 'Endless'))
@section('page-title', 'עריכת עמוד ההנצחה')
@section('page-sub', 'כל שינוי נשמר ומתעדכן מיד בעמוד הציבורי')

@section('page-actions')
    <a href="{{ route('memorials.show', $memorial) }}" target="_blank" rel="noopener" class="btn btn--ghost">תצוגה מקדימה</a>
@endsection

@section('dashboard')

<form method="POST" action="{{ route('dashboard.memorial.update') }}" class="form">
    @csrf
    @method('PUT')

    {{-- ------------------------------------------------------------ details --}}
    <section class="accordion" id="details" data-accordion data-open>
        <button type="button" class="accordion__head" aria-expanded="true">
            <h2>פרטים אישיים</h2>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="accordion__body">
            <div class="form-row form-row--2">
                <x-field name="first_name" label="שם פרטי" :value="$memorial->first_name" required />
                <x-field name="last_name" label="שם משפחה" :value="$memorial->last_name" />
            </div>

            <div class="form-row form-row--2">
                <div>
                    <div class="radio-group" role="radiogroup" aria-label="מגדר">
                        @foreach ($genders as $gender)
                            <label>
                                <input type="radio" name="gender" value="{{ $gender->value }}" @checked(old('gender', $memorial->gender->value) === $gender->value)>
                                <span>{{ $gender->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <x-field name="subtitle" label="כותרת עליונה" :value="$memorial->subtitle" hint="ברירת מחדל: לזכרו / לזכרה של יקירנו" />
            </div>

            <div class="form-row form-row--2">
                <x-field name="birth_date" label="תאריך לידה" type="date" :value="$memorial->birth_date?->format('Y-m-d')" />
                <x-field name="death_date" label="תאריך פטירה" type="date" :value="$memorial->death_date?->format('Y-m-d')" />
            </div>

            <div class="form-row form-row--2">
                <x-field name="dates_text" label="תאריכים (טקסט חופשי)" :value="$memorial->dates_text" hint="אם מולא — יוצג במקום התאריכים שלמעלה" />
                <x-field name="hebrew_dates" label="תאריכים עבריים" :value="$memorial->hebrew_dates" />
            </div>

            <div class="form-row form-row--2">
                <x-religion-select
                    name="religion"
                    :options="$religions"
                    :selected="old('religion', $memorial->religion->value)" />
                <x-field name="founder_name" label="הוקם ע״י" :value="$memorial->founder_name" />
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------- biography --}}
    <section class="accordion" id="bio" data-accordion>
        <button type="button" class="accordion__head" aria-expanded="false">
            <h2>ביוגרפיה</h2>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="accordion__body" hidden>
            <div class="form-row">
                <x-field name="biography_title" label="כותרת הביוגרפיה" :value="$memorial->biography_title" hint="למשל: בעל, אב, סב, אח, איש משפחה וגיבור" />
            </div>
            <x-editor name="biography" :value="$memorial->biography" label="הטקסט" placeholder="ספרו על חייו, על מי שהיה, על מה שנשאר…" />
        </div>
    </section>

    {{-- -------------------------------------------------------------- quote --}}
    <section class="accordion" id="quote" data-accordion>
        <button type="button" class="accordion__head" aria-expanded="false">
            <h2>ציטוט</h2>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="accordion__body" hidden>
            <div class="form-row">
                <div class="field field--textarea">
                    <textarea name="quote" id="quote" placeholder=" " rows="3">{{ old('quote', $memorial->quote) }}</textarea>
                    <label for="quote">הציטוט</label>
                </div>
            </div>
            <div class="form-row">
                <x-field name="quote_name" label="שם המצטט" :value="$memorial->quote_name" />
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------ privacy --}}
    <section class="accordion" id="privacy" data-accordion>
        <button type="button" class="accordion__head" aria-expanded="false">
            <h2>כתובת ופרטיות</h2>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="accordion__body" hidden>
            <div class="form-row">
                <x-field name="slug" label="כתובת העמוד" :value="$memorial->slug" required dir="ltr"
                         hint="{{ url('/m') }}/..." />
            </div>

            <div class="form-row form-row--2">
                <div class="field field--always">
                    <select name="visibility" id="visibility">
                        <option value="private" @selected(old('visibility', $memorial->visibility) === 'private')>פרטי — רק מי שיש לו קישור</option>
                        <option value="unlisted" @selected(old('visibility', $memorial->visibility) === 'unlisted')>לא מאונדקס — נגיש בקישור, ללא מנועי חיפוש</option>
                    </select>
                    <label for="visibility">פרטיות</label>
                </div>
                <div style="display: flex; flex-direction: column; gap: 14px; justify-content: center;">
                    <label class="toggle">
                        <input type="checkbox" name="require_approval" value="1" @checked(old('require_approval', $memorial->require_approval))>
                        <span class="track" aria-hidden="true"></span>
                        <span>זיכרונות חדשים דורשים את אישורי</span>
                    </label>
                    <label class="toggle">
                        <input type="checkbox" name="notify_owner" value="1" @checked(old('notify_owner', $memorial->notify_owner))>
                        <span class="track" aria-hidden="true"></span>
                        <span>שליחת מייל אליי על כל זיכרון חדש</span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <div style="display: flex; gap: 12px; margin-block-start: 24px;">
        <button type="submit" class="btn">שמירת השינויים</button>
        <a href="{{ route('dashboard.index') }}" class="btn btn--ghost">ביטול</a>
    </div>
</form>

{{-- --------------------------------------------------------------- media --}}
<section class="accordion" id="media" data-accordion style="margin-block-start: 24px;">
    <button type="button" class="accordion__head" aria-expanded="false">
        <h2>תמונות וידאו ראשיים</h2>
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="accordion__body" hidden>
        <div class="grid grid--2">
            @foreach ([
                ['type' => 'portrait_image', 'label' => 'תמונת פרופיל', 'current' => $memorial->portrait_url, 'accept' => 'image/*', 'kind' => 'image'],
                ['type' => 'portrait_video', 'label' => 'סרטון פרופיל (במקום תמונה)', 'current' => $memorial->portrait_video_path ? media_url($memorial->portrait_video_path) : null, 'accept' => 'video/mp4,video/webm', 'kind' => 'video'],
                ['type' => 'hero_image', 'label' => 'תמונת רקע לראש העמוד', 'current' => $memorial->hero_image_url, 'accept' => 'image/*', 'kind' => 'image'],
                ['type' => 'hero_video', 'label' => 'סרטון רקע לראש העמוד', 'current' => $memorial->hero_video_url, 'accept' => 'video/mp4,video/webm', 'kind' => 'video'],
            ] as $slot)
                <div class="card">
                    <div class="card__head"><h2 style="font-size: 16px;">{{ $slot['label'] }}</h2></div>
                    <div class="card__body">
                        @if ($slot['current'])
                            <div style="margin-block-end: 14px; border-radius: var(--r-img); overflow: hidden; background: var(--soft);">
                                @if ($slot['kind'] === 'video')
                                    <video src="{{ $slot['current'] }}" controls muted style="width: 100%; max-height: 220px; object-fit: cover;"></video>
                                @else
                                    <img src="{{ $slot['current'] }}" alt="" style="width: 100%; max-height: 220px; object-fit: cover;">
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('dashboard.memorial.media.store', $slot['type']) }}" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            @csrf
                            <input type="file" name="file" accept="{{ $slot['accept'] }}" required
                                   style="flex: 1; min-width: 0; font-size: var(--fs-micro);">
                            <button type="submit" class="btn btn--sm">העלאה</button>
                        </form>

                        @if ($slot['current'])
                            <form method="POST" action="{{ route('dashboard.memorial.media.destroy', $slot['type']) }}" style="margin-block-start: 10px;"
                                  onsubmit="return confirm('להסיר את הקובץ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">הסרה</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ------------------------------------------------------------- gallery --}}
<section class="accordion" id="gallery" data-accordion style="margin-block-start: 16px;">
    <button type="button" class="accordion__head" aria-expanded="false">
        <h2>גלריית "רגעים של אהבה" ({{ $memorial->images->count() }})</h2>
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="accordion__body" hidden>
        <form method="POST" action="{{ route('dashboard.gallery.store') }}" enctype="multipart/form-data" style="margin-block-end: 24px;">
            @csrf
            <x-uploader name="images" :max="30" :videos="false" title="הוספת תמונות לגלריה" />
            <button type="submit" class="btn" style="margin-block-start: 16px;">העלאה לגלריה</button>
        </form>

        @if ($memorial->images->isNotEmpty())
            <div class="previews" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
                @foreach ($memorial->images as $image)
                    <div class="preview" style="aspect-ratio: 3/4;">
                        <img src="{{ $image->thumb_url }}" alt="{{ $image->alt }}">
                        <form method="POST" action="{{ route('dashboard.gallery.destroy', $image) }}" onsubmit="return confirm('למחוק את התמונה?')">
                            @csrf @method('DELETE')
                            <button type="submit" aria-label="מחיקה">&times;</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="muted" style="font-size: var(--fs-small);">עדיין אין תמונות בגלריה.</p>
        @endif
    </div>
</section>
@endsection
