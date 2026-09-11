<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'max:0'], // honeypot
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'שם מלא', 'email' => 'דוא״ל', 'phone' => 'טלפון', 'message' => 'הודעה'];
    }
}
