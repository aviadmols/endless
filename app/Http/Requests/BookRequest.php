<?php

namespace App\Http\Requests;

use App\Enums\BookContent;
use App\Enums\BookCover;
use App\Enums\BookSize;
use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', Rule::enum(BookContent::class)],
            'size' => ['required', Rule::enum(BookSize::class)],
            'cover' => ['required', Rule::enum(BookCover::class)],
            'copies' => ['required', 'integer', 'min:1', 'max:'.Book::MAX_COPIES],
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'תוכן הספר',
            'size' => 'גודל',
            'cover' => 'צבע הכריכה',
            'copies' => 'מספר עותקים',
        ];
    }
}
