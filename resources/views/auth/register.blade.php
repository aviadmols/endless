@extends('layouts.app')

@section('title', 'יצירת עמוד הנצחה | ' . setting('general.site_name', 'Endless'))

@section('content')
<div class="scene">
    <div class="scene__inner wrap wrap--form">
        <div class="paper-card fade-in">
            <div class="paper-card__body">

                <div class="center" style="margin-block-end: 34px;">
                    <h1 class="page-title">יצירת עמוד</h1>
                    <p class="muted" style="margin-block-start: 12px; font-size: var(--fs-body);">התחילו בחינם. ללא כרטיס אשראי.</p>
                </div>

                <x-alerts />

                <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" class="form">
                    @csrf

                    <h2 class="eyebrow" style="font-size: 14px; letter-spacing: 2px; margin-block-end: 16px;">לזכרו של</h2>

                    <div class="form-row form-row--2">
                        <x-field name="deceased_first_name" label="שם פרטי" required />
                        <x-field name="deceased_last_name" label="שם משפחה" />
                    </div>

                    <div class="form-row form-row--2">
                        <x-field name="deceased_birth_date" label="תאריך לידה" type="date" hint="לא חובה" />
                        <x-field name="deceased_death_date" label="תאריך פטירה" type="date" required />
                    </div>

                    <div class="form-row form-row--2">
                        <div>
                            <div class="radio-group" role="radiogroup" aria-label="מגדר">
                                @foreach (\App\Enums\Gender::cases() as $gender)
                                    <label>
                                        <input type="radio" name="deceased_gender" value="{{ $gender->value }}" @checked(old('deceased_gender', 'male') === $gender->value) required>
                                        <span>{{ $gender->label() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('deceased_gender')<span class="error" style="color: var(--danger); font-size: var(--fs-micro);">{{ $message }}</span>@enderror
                        </div>

                        <div @class(['field', 'field--always', 'has-error' => $errors->has('deceased_religion')])>
                            <select name="deceased_religion" id="deceased_religion" required>
                                @foreach ($religions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('deceased_religion', 'jewish') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label for="deceased_religion">סמל דת</label>
                            @error('deceased_religion')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div style="margin-block-end: 20px;">
                        <x-uploader name="deceased_image" :max="10" :videos="false" title="העלאת תמונות" hint="התמונה הראשונה תשמש כתמונת הפרופיל · JPG, PNG, WebP, GIF · עד 8MB" />
                    </div>

                    <div class="form-row">
                        <div @class(['field', 'field--textarea', 'has-error' => $errors->has('deceased_bio')])>
                            <textarea name="deceased_bio" id="deceased_bio" placeholder=" " rows="6">{{ old('deceased_bio') }}</textarea>
                            <label for="deceased_bio">כמה מילים על האדם…</label>
                            @error('deceased_bio')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <p class="form-note muted">אפשר להוסיף פרטים, תמונות וזיכרונות ולערוך הכל בהמשך מהאזור האישי.</p>

                    <hr class="hr">

                    <h2 class="eyebrow" style="font-size: 14px; letter-spacing: 2px; margin-block-end: 16px;">הפרטים שלכם</h2>

                    <div class="form-row form-row--2">
                        <x-field name="creator_first_name" label="השם הפרטי שלך" required />
                        <x-field name="creator_last_name" label="שם המשפחה שלך" required />
                    </div>

                    <div class="form-row form-row--3">
                        <x-field name="creator_email" label="כתובת אימייל" type="email" required />

                        <div class="field field--always">
                            <select name="country_code" id="country_code" required>
                                @foreach ($countries as $code => $country)
                                    <option value="{{ $code }}" @selected(old('country_code', config('endless.phone.default_country')) === (string) $code)>
                                        {{ $country['flag'] }} {{ $country['name'] }} (+{{ $code }})
                                    </option>
                                @endforeach
                            </select>
                            <label for="country_code">מדינה</label>
                        </div>

                        <x-field name="creator_phone" label="מספר טלפון" type="tel" required placeholder="50-0000000" always />
                    </div>

                    <p class="form-note muted" style="margin-block-end: 20px;">נשלח אליכם קוד אימות ב-SMS או במייל כדי להיכנס לאזור האישי. אין צורך בסיסמה.</p>

                    <label class="checkbox" style="margin-block-end: 24px;">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                        <span class="box" aria-hidden="true"></span>
                        <span>קראתי ואני מאשר/ת את תנאי השימוש ומדיניות הפרטיות.</span>
                    </label>
                    @error('terms')<p class="error" style="color: var(--danger); font-size: var(--fs-micro); margin-block-end: 14px;">{{ $message }}</p>@enderror

                    <button type="submit" class="btn btn--block btn--lg">יצירת עמוד הנצחה</button>
                </form>

                <p class="center micro" style="margin-block-start: 22px;">
                    כבר יש לכם עמוד? <a href="{{ route('login') }}" class="link-underline">כניסה לאזור האישי</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
