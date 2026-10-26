<?php

namespace App\Http\Requests;

use App\Models\ChildPickupContact;
use App\Models\Person;
use App\Models\RfidCard;
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
        $roleCode = SacRole::find($this->input('sac_role_id'))?->code;
        $existingPerson = $this->route('id') ? Person::find($this->route('id')) : null;
        $photoRequired = in_array($roleCode, [Person::ROLE_PARENT, Person::ROLE_STUDENT], true)
            && (! $existingPerson?->image_path || ($this->exists('image_path') && ! $this->hasFile('image_path')));
        $detailsPresence = in_array(
            $roleCode,
            [Person::ROLE_PARENT, Person::ROLE_STAFF],
            true
        ) ? 'required' : 'nullable';

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date'],
            'sac_role_id' => ['required', 'integer', 'exists:sac_role,id'],
            'guardian_relationships' => ['sometimes', 'array'],
            'guardian_relationships.*' => ['array'],
            'guardian_relationships.*.relationship' => ['nullable', 'string', 'max:100'],
            'guardian_relationships.*.is_primary_contact' => ['sometimes', 'boolean'],
            'email' => [$detailsPresence, 'email', 'max:255'],
            'pickup_contacts' => ['sometimes', 'array', 'max:2'],
            'pickup_contacts.*' => ['array'],
            'pickup_contacts.*.first_name' => ['nullable', 'string', 'max:255', 'required_with:pickup_contacts.*.last_name'],
            'pickup_contacts.*.last_name' => ['nullable', 'string', 'max:255', 'required_with:pickup_contacts.*.first_name'],
            'pickup_contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'pickup_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'pickup_contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'pickup_contacts.*.address' => ['nullable', 'string', 'max:255'],
            'pickup_contacts.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'pickup_contacts.*.remove_image' => ['sometimes', 'boolean'],
            'pickup_contacts.*.is_parent' => ['sometimes', 'boolean'],
            'pickup_contacts.*.can_pick_up' => ['sometimes', 'boolean'],
            'pickup_contacts.*.can_drop_off' => ['sometimes', 'boolean'],
            'pickup_contacts.*.rfid_uid' => ['nullable', 'string', 'max:64', 'regex:/^[0-9A-Za-z :\-]+$/'],
            'phone' => [$detailsPresence, 'string', 'max:50'],
            'alt_email' => ['nullable', 'email', 'max:255'],
            'alt_phone' => ['nullable', 'string', 'max:50'],
            'Occupation' => [$detailsPresence, 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'regex:/^\d{10}$/D'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => [$detailsPresence, 'string', 'max:100'],
            'state' => [$detailsPresence, 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'nationality' => [$detailsPresence, 'string', 'max:100'],
            'state_of_origin' => ['nullable', 'string', 'max:100'],
            'lga' => ['nullable', 'string', 'max:100'],
            'image_path' => [$photoRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];

        $section = match ($roleCode) {
            Person::ROLE_PARENT => 'parent',
            Person::ROLE_STUDENT => 'student',
            default => null,
        };
        if ($section) {
            foreach (config('registration.'.$section.'.fields', []) as $name => $definition) {
                $fieldRules = $rules[$name] ?? ['nullable', ($definition['type'] ?? '') === 'date' ? 'date' : 'string', 'max:255'];
                $fieldRules[0] = ($definition['required'] ?? false) ? 'required' : 'nullable';
                $rules[$name] = $definition['rules'] ?? $fieldRules;
            }
        }
        foreach (config('registration.pickup.fields', []) as $name => $definition) {
            $rules['pickup_contacts.*.'.$name] ??= $definition['rules'] ?? ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = SacRole::find($this->input('sac_role_id'));

            if (in_array($role?->code, [Person::ROLE_STAFF, Person::ROLE_ADMIN, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY], true)) {
                foreach (['bank_name' => 'Bank name', 'account_name' => 'Account name', 'account_number' => 'Account number'] as $field => $label) {
                    if (! filled($this->input($field))) {
                        $validator->errors()->add($field, $label.' is required for payroll-eligible people.');
                    }
                }
            }

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

            $existingLinks = $this->route('id')
                ? ChildPickupContact::where('child_id', $this->route('id'))->with('contact')->get()->keyBy('slot')
                : collect();

            foreach ($this->input('pickup_contacts', []) as $index => $contact) {
                if (! is_array($contact)) {
                    continue;
                }

                $hasAnyValue = collect($contact)
                    ->except(['can_pick_up', 'can_drop_off', 'image', 'remove_image', 'is_parent'])
                    ->contains(fn ($value) => filled($value));

                if (! $hasAnyValue) {
                    continue;
                }

                foreach (config('registration.pickup.fields', []) as $field => $definition) {
                    if (! ($definition['required'] ?? false)) {
                        continue;
                    }
                    if (! filled($contact[$field] ?? null)) {
                        $validator->errors()->add(
                            "pickup_contacts.{$index}.{$field}",
                            ucfirst(str_replace('_', ' ', $field)).' is required for this contact.'
                        );
                    }
                }

                $existingPhoto = $existingLinks->get(((int) $index) + 1)?->contact?->image_path;
                $removingPhoto = filter_var($contact['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if (! $this->hasFile("pickup_contacts.{$index}.image") && (! $existingPhoto || $removingPhoto)) {
                    $validator->errors()->add("pickup_contacts.{$index}.image", 'A photo is required for this contact.');
                }
            }

            $this->validateRfidCards($validator);
        });
    }

    protected function validateRfidCards($validator): void
    {
        $childId = $this->route('id');
        $existingLinks = $childId
            ? ChildPickupContact::query()->where('child_id', $childId)->get()->keyBy('slot')
            : collect();
        $seen = [];

        foreach ($this->input('pickup_contacts', []) as $index => $contact) {
            if (! is_array($contact) || ! is_string($contact['rfid_uid'] ?? null)) {
                continue;
            }

            $uid = RfidCard::normalize($contact['rfid_uid']);
            if ($uid === null) {
                continue;
            }

            $field = "pickup_contacts.{$index}.rfid_uid";
            $email = filled($contact['email'] ?? null) ? mb_strtolower(trim($contact['email'])) : null;

            if (isset($seen[$uid]) && $seen[$uid] !== $email) {
                $validator->errors()->add($field, 'Each contact needs a different card.');
                continue;
            }
            $seen[$uid] = $email;

            $card = RfidCard::query()->active()->where('uid', $uid)->with('contact')->first();
            if (! $card?->contact) {
                continue;
            }

            $slotContactId = $existingLinks->get(((int) $index) + 1)?->pickup_contact_id;
            $sameContact = (int) $card->pickup_contact_id === (int) $slotContactId
                || ($email !== null && mb_strtolower((string) $card->contact->email) === $email);

            if (! $sameContact) {
                $validator->errors()->add($field, "This card is already assigned to {$card->contact->full_name}.");
            }
        }
    }
}
