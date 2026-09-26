<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date'],
            'sac_role_id' => ['required', 'integer', 'exists:sac_role,id'],
            'guardian_relationships' => ['sometimes', 'array'],
            'guardian_relationships.*' => ['array'],
            'guardian_relationships.*.relationship' => ['nullable', 'string', 'max:100'],
            'guardian_relationships.*.is_primary_contact' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alt_email' => ['nullable', 'email', 'max:255'],
            'alt_phone' => ['nullable', 'string', 'max:50'],
            'Occupation' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'state_of_origin' => ['nullable', 'string', 'max:100'],
            'lga' => ['nullable', 'string', 'max:100'],
            'image_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];
    }
}
