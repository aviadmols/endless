<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'creator_email' => mb_strtolower(trim((string) $this->input('creator_email'))),
            'creator_phone_e164' => PhoneNumber::normalize($this->input('creator_phone'), $this->input('country_code')),
        ]);
    }

    public function rules(): array
    {
        $maxKb = (int) config('endless.uploads.image_max_kb', 8192);
        $mimes = implode(',', config('endless.uploads.image_mimes', ['jpg', 'jpeg', 'png', 'webp']));

        return [
            'deceased_first_name' => ['required', 'string', 'max:80'],
            'deceased_last_name' => ['nullable', 'string', 'max:80'],
            'deceased_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'deceased_death_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:deceased_birth_date'],
            'deceased_gender' => ['required', Rule::enum(Gender::class)],
            'deceased_religion' => ['required', Rule::enum(Religion::class)],
            'deceased_image' => ['nullable', 'array', 'max:'.config('endless.uploads.memory_max_images', 10)],
            'deceased_image.*' => ['image', 'mimes:'.$mimes, 'max:'.$maxKb],
            'deceased_bio' => ['nullable', 'string', 'max:6000'],
            'creator_first_name' => ['required', 'string', 'max:80'],
            'creator_last_name' => ['required', 'string', 'max:80'],
            'creator_email' => ['required', 'email:rfc', 'max:160'],
            'country_code' => ['required', Rule::in(array_keys(config('endless.phone.countries', [])))],
            'creator_phone' => ['required', 'string', 'max:32'],
            'creator_phone_e164' => ['required', 'string'],
            'terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'deceased_first_name' => 'שם פרטי',
            'deceased_last_name' => 'שם משפחה',
            'deceased_birth_date' => 'תאריך לידה',
            'deceased_death_date' => 'תאריך פטירה',
            'deceased_gender' => 'מגדר',
            'deceased_religion' => 'דת',
            'deceased_image' => 'תמונות',
            'deceased_image.*' => 'תמונה',
            'deceased_bio' => 'כמה מילים',
            'creator_first_name' => 'השם הפרטי שלך',
            'creator_last_name' => 'שם המשפחה שלך',
            'creator_email' => 'כתובת אימייל',
            'country_code' => 'מדינה',
            'creator_phone' => 'מספר טלפון',
            'creator_phone_e164' => 'מספר טלפון',
            'terms' => 'תנאי השימוש',
        ];
    }

    public function messages(): array
    {
        return [
            'creator_phone_e164.required' => 'מספר הטלפון אינו תקין.',
            'terms.accepted' => 'יש לאשר את תנאי השימוש ומדיניות הפרטיות.',
            'deceased_death_date.after_or_equal' => 'תאריך הפטירה חייב להיות אחרי תאריך הלידה.',
        ];
    }
}
