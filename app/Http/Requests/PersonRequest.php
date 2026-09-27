<?php

namespace App\Http\Requests;

use App\Models\Person;
use App\Models\SacRole;
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
            'email' => ['nullable', 'email', 'max:255'],
            'pickup_contacts' => ['sometimes', 'array', 'max:3'],
            'pickup_contacts.*' => ['array'],
            'pickup_contacts.*.first_name' => ['nullable', 'string', 'max:255', 'required_with:pickup_contacts.*.last_name'],
            'pickup_contacts.*.last_name' => ['nullable', 'string', 'max:255', 'required_with:pickup_contacts.*.first_name'],
            'pickup_contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'pickup_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'pickup_contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'pickup_contacts.*.address' => ['nullable', 'string', 'max:255'],
            'pickup_contacts.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'pickup_contacts.*.can_pick_up' => ['sometimes', 'boolean'],
            'pickup_contacts.*.can_drop_off' => ['sometimes', 'boolean'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = SacRole::find($this->input('sac_role_id'));

            if ($role?->code !== Person::ROLE_STUDENT) {
                return;
            }

            $contacts = collect($this->input('pickup_contacts', []))
                ->filter(fn ($contact) => is_array($contact)
                    && filled($contact['first_name'] ?? null)
                    && filled($contact['last_name'] ?? null));

            if ($contacts->isEmpty()) {
                $validator->errors()->add(
                    'pickup_contacts',
                    'At least one pickup or drop-off contact is required.'
                );
            }

            foreach ($this->input('pickup_contacts', []) as $index => $contact) {
                if (! is_array($contact)) {
                    continue;
                }

                $hasAnyValue = collect($contact)
                    ->except(['can_pick_up', 'can_drop_off', 'image'])
                    ->contains(fn ($value) => filled($value));

                if (! $hasAnyValue) {
                    continue;
                }

                foreach (['first_name', 'last_name', 'relationship', 'email', 'phone', 'address'] as $field) {
                    if (! filled($contact[$field] ?? null)) {
                        $validator->errors()->add(
                            "pickup_contacts.{$index}.{$field}",
                            ucfirst(str_replace('_', ' ', $field)).' is required for this contact.'
                        );
                    }
                }
            }
        });
    }
}
