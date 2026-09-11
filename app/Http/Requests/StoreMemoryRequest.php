<?php

namespace App\Http\Requests;

use App\Rules\MemoryMedia;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:120'],
            'author_email' => ['nullable', 'email:rfc', 'max:160'],
            'author_phone' => ['nullable', 'string', 'max:32'],
            'title' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:40000', function (string $attribute, mixed $value, \Closure $fail) {
                if (mb_strlen(trim(strip_tags((string) $value))) < 2) {
                    $fail('כתבו כמה מילים על הזיכרון.');
                }
            }],
            'media' => ['nullable', 'array', 'max:'.config('endless.uploads.memory_max_images', 10)],
            'media.*' => ['file', new MemoryMedia],
            'website' => ['nullable', 'max:0'], // honeypot
        ];
    }

    public function attributes(): array
    {
        return [
            'author_name' => 'השם שלך',
            'author_email' => 'אימייל',
            'author_phone' => 'טלפון',
            'title' => 'כותרת',
            'body' => 'הזיכרון',
            'media' => 'תמונות וסרטונים',
            'media.*' => 'קובץ',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'כתבו כמה מילים על הזיכרון.',
            'media.max' => 'ניתן להעלות עד :max קבצים.',
        ];
    }
}
