@extends('layouts.admin')

@section('title', $def['title'] . ' | ניהול')
@section('page-title', $def['title'])
@section('page-sub', $def['intro'])

@section('admin')

<div class="tabs" style="margin-block-end: 22px;">
    @foreach ($groups as $key => $meta)
        <a href="{{ route('admin.settings.edit', $key) }}" @class(['is-active' => $group === $key])>{{ $meta['title'] }}</a>
    @endforeach
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="{{ route('admin.settings.update', $group) }}" enctype="multipart/form-data" class="form">
            @csrf
            @method('PUT')

            @foreach ($def['fields'] as $key => $field)
                @php($input = str_replace('.', '__', $key))
                <div style="margin-block-end: 24px;">
                    @switch($field['type'])

                        @case('toggle')
                            <label class="toggle">
                                <input type="checkbox" name="{{ $input }}" value="1" @checked(old($input, $values[$key]) === '1')>
                                <span class="track" aria-hidden="true"></span>
                                <span>{{ $field['label'] }}</span>
                            </label>
                            @break

                        @case('select')
                            <div class="field-block">
                                <label for="{{ $input }}">{{ $field['label'] }}</label>
                                <select name="{{ $input }}" id="{{ $input }}">
                                    @foreach ($field['options'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old($input, $values[$key]) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @break

                        @case('secret')
                            <div class="field-block">
                                <label for="{{ $input }}">{{ $field['label'] }}</label>
                                <input type="password" name="{{ $input }}" id="{{ $input }}" autocomplete="new-password"
                                       placeholder="{{ ($hasSecret[$key] ?? false) ? '•••••••• (שמור — השאירו ריק כדי לא לשנות)' : '' }}">
                                @if ($hasSecret[$key] ?? false)
                                    <label class="checkbox" style="margin-block-start: 6px;">
                                        <input type="checkbox" name="{{ $input }}__clear" value="1">
                                        <span class="box" aria-hidden="true"></span>
                                        <span class="hint">מחיקת הערך השמור</span>
                                    </label>
                                @endif
                            </div>
                            @break

                        @case('textarea')
                            <div class="field-block">
                                <label for="{{ $input }}">{{ $field['label'] }}</label>
                                <textarea name="{{ $input }}" id="{{ $input }}" rows="5">{{ old($input, $values[$key]) }}</textarea>
                            </div>
                            @break

                        @case('richtext')
                            <x-editor :name="$input" :value="$values[$key]" :label="$field['label']" />
                            @break

                        @case('image')
                            <div class="field-block">
                                <label for="{{ $input }}">{{ $field['label'] }}</label>
                                @if ($values[$key])
                                    <div style="display: flex; align-items: center; gap: 14px; margin-block-end: 8px;">
                                        <img src="{{ media_url($values[$key]) }}" alt="" style="height: 54px; width: auto; background: var(--soft); border-radius: 8px; padding: 6px;">
                                        <label class="checkbox">
                                            <input type="checkbox" name="{{ $input }}__remove" value="1">
                                            <span class="box" aria-hidden="true"></span>
                                            <span class="hint">הסרה</span>
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="{{ $input }}" id="{{ $input }}" accept=".svg,image/*">
                            </div>
                            @break

                        @case('features')
                            @php($items = json_decode((string) $values[$key], true) ?: [])
                            <div class="field-block">
                                <label>{{ $field['label'] }}</label>
                                @for ($i = 0; $i < 4; $i++)
                                    <div style="display: flex; gap: 10px; margin-block-end: 8px;">
                                        <select name="{{ $input }}[{{ $i }}][icon]" style="flex: 0 0 150px;">
                                            @foreach (['dove' => 'יונה', 'heart' => 'לב', 'cloud' => 'ענן', 'hand' => 'יד', 'album' => 'אלבום', 'shield' => 'מגן'] as $iconKey => $iconLabel)
                                                <option value="{{ $iconKey }}" @selected(($items[$i]['icon'] ?? '') === $iconKey)>{{ $iconLabel }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="{{ $input }}[{{ $i }}][text]" value="{{ $items[$i]['text'] ?? '' }}" placeholder="טקסט" style="flex: 1;">
                                    </div>
                                @endfor
                            </div>
                            @break

                        @default
                            <div class="field-block">
                                <label for="{{ $input }}">{{ $field['label'] }}</label>
                                <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'email' ? 'email' : 'text') }}"
                                       name="{{ $input }}" id="{{ $input }}"
                                       value="{{ old($input, $values[$key]) }}"
                                       placeholder="{{ $field['placeholder'] ?? '' }}"
                                       @if ($field['type'] === 'email' || $key === 'mail.host') dir="ltr" @endif>
                            </div>
                    @endswitch
                </div>
            @endforeach

            <button type="submit" class="btn">שמירת ההגדרות</button>
        </form>
    </div>
</div>

@if ($def['test'] ?? null)
    <div class="card">
        <div class="card__head"><h2>{{ $def['test']['label'] }}</h2></div>
        <div class="card__body">
            <form method="POST" action="{{ route($def['test']['route']) }}" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-start;">
                @csrf
                <input type="{{ $def['test']['input'] }}"
                       name="{{ $group === 'mail' ? 'test_email' : 'test_phone' }}"
                       placeholder="{{ $def['test']['placeholder'] }}"
                       dir="ltr"
                       style="flex: 1; min-width: 220px; padding: 12px 15px; border: 1px solid var(--rule); border-radius: var(--r-input); font-family: var(--font);">
                <button type="submit" class="btn">{{ $def['test']['label'] }}</button>
            </form>
            <p class="micro" style="margin-block-start: 10px;">
                @if ($group === 'sms')
                    הבדיקה שולחת הודעה אמיתית דרך 019SMS (אם ההגדרה פעילה). שימו לב לעלות ההודעה.
                @else
                    הבדיקה שולחת מייל אמיתי דרך שרת ה-SMTP שהוגדר (אם ההגדרה פעילה).
                @endif
            </p>
        </div>
    </div>
@endif
@endsection
