<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\Religion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memorial = $this->route('memorial') ?? $this->user()?->primaryMemorial();

        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'dates_text' => ['nullable', 'string', 'max:120'],
            'hebrew_dates' => ['nullable', 'string', 'max:120'],
            'religion' => ['required', Rule::enum(Religion::class)],
            'video_url' => ['nullable', 'url', 'max:500'],
            'biography_title' => ['nullable', 'string', 'max:160'],
            'biography' => ['nullable', 'string', 'max:60000'],
            'quote' => ['nullable', 'string', 'max:600'],
            'quote_name' => ['nullable', 'string', 'max:120'],
            'founder_name' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('memorials', 'slug')->ignore($memorial?->id)],
            'visibility' => ['required', Rule::in(['private', 'unlisted'])],
            'require_approval' => ['nullable', 'boolean'],
            'notify_owner' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'שם פרטי',
            'last_name' => 'שם משפחה',
            'gender' => 'מגדר',
            'subtitle' => 'כותרת עליונה',
            'birth_date' => 'תאריך לידה',
            'death_date' => 'תאריך פטירה',
            'dates_text' => 'תאריכים (טקסט חופשי)',
            'hebrew_dates' => 'תאריכים עבריים',
            'religion' => 'סמל דת',
            'video_url' => 'קישור לווידאו',
            'biography_title' => 'כותרת הביוגרפיה',
            'biography' => 'ביוגרפיה',
            'quote' => 'ציטוט',
            'quote_name' => 'שם המצטט',
            'founder_name' => 'הוקם ע״י',
            'slug' => 'כתובת העמוד',
            'visibility' => 'פרטיות',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'כתובת העמוד יכולה להכיל אותיות באנגלית, מספרים ומקפים בלבד.',
            'slug.unique' => 'כתובת העמוד הזו כבר תפוסה.',
        ];
    }
}
