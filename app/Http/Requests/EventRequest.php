<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Library\Validation\Rules\ValidUploadMultiple;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'images' => ValidUploadMultiple::field('nullable|max:8')->file('file|image|mimes:jpg,jpeg,png,webp|max:4096'),
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
