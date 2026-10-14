<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class AcademyFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check()
            && in_array(backpack_user()?->person?->sacRole?->code, Person::ROLES_MANAGE_ACCOUNTS, true);
    }

    public function rules(): array
    {
        return [
            'fee_name' => ['required', 'string', 'max:150'],
            'age_or_level' => ['required', 'string', 'max:150'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'schedule' => ['required', 'string', 'max:180'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}