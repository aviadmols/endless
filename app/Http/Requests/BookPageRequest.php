<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;

class BookPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'eyebrow' => ['nullable', 'string', 'max:200'],
            'title' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:20000'],
            'caption' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function attributes(): array
    {
        return [
            'eyebrow' => 'כותרת עליונה',
            'title' => 'כותרת',
            'body' => 'טקסט',
            'caption' => 'שורת סיום',
        ];
    }

    /** Only the text fields the form actually submitted. */
    public function fields(): array
    {
        return array_intersect_key($this->all(), array_flip(Book::EDITABLE_FIELDS));
    }
}
