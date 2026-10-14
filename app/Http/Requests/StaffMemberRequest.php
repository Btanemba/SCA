<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'person_id' => [
                'required',
                'integer',
                'exists:persons,id',
                Rule::unique('staff_members', 'person_id')->ignore($this->route('id')),
                function ($attribute, $value, $fail): void {
                    $isEligibleStaff = Person::query()
                        ->whereKey($value)
                        ->whereHas('sacRole', fn ($query) => $query->whereIn('code', [Person::ROLE_STAFF, Person::ROLE_ADMIN]))
                        ->exists();

                    if (! $isEligibleStaff) {
                        $fail('Select a person with the Staff or Administrator role.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
