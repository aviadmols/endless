<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('endless.uploads.image_max_kb', 8192);
        $mimes = implode(',', config('endless.uploads.image_mimes', ['jpg', 'jpeg', 'png', 'webp']));

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
            'images' => ['nullable', 'array', 'max:'.config('endless.uploads.memory_max_images', 10)],
            'images.*' => ['image', 'mimes:'.$mimes, 'max:'.$maxKb],
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
            'images' => 'תמונות',
            'images.*' => 'תמונה',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'כתבו כמה מילים על הזיכרון.',
            'images.max' => 'ניתן להעלות עד :max תמונות.',
            'images.*.max' => 'כל תמונה יכולה להיות עד 8MB.',
            'images.*.image' => 'ניתן להעלות קבצי תמונה בלבד (JPG, PNG, WebP, GIF).',
        ];
    }
}
