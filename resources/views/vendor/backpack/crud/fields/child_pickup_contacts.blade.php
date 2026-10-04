@php
    $contacts = collect();

    if ($entry instanceof \App\Models\Person) {
        $contacts = $entry->pickupContactLinks->keyBy('slot');
    }

    $oldContacts = old('pickup_contacts', []);
    $relationshipOptions = $field['relationship_options'] ?? [];
    $highestSavedSlot = (int) $contacts->keys()->max();
    $visibleSlots = max(1, min(2, max($highestSavedSlot, count($oldContacts))));
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{{ $field['label'] ?? 'Pickup and drop-off contacts' }}</label>
    <p class="text-muted small">Add up to two people authorized to pick up or drop off this child.</p>
    <p class="text-muted small"><span class="text-danger">*</span> Required field</p>

    <div class="row g-3">
        @for ($slot = 1; $slot <= 2; $slot++)
            @php
                $link = $contacts->get($slot);
                $contact = $link?->contact;
                $oldContact = $oldContacts[$slot - 1] ?? [];
                $removeImage = filter_var($oldContact['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $value = fn (string $key) => array_key_exists($key, $oldContact)
                    ? $oldContact[$key]
                    : $contact?->{$key};
                $relationshipValue = $value('relationship');
                $canPickUp = array_key_exists('can_pick_up', $oldContact)
                    ? filter_var($oldContact['can_pick_up'], FILTER_VALIDATE_BOOLEAN)
                    : ($link?->can_pick_up ?? true);
                $canDropOff = array_key_exists('can_drop_off', $oldContact)
                    ? filter_var($oldContact['can_drop_off'], FILTER_VALIDATE_BOOLEAN)
                    : ($link?->can_drop_off ?? true);
                $slotIsVisible = $slot <= $visibleSlots;
            @endphp
            <div class="col-12 pickup-contact-slot{{ $slot > $visibleSlots ? ' d-none' : '' }}" data-slot="{{ $slot }}">
                <div class="border rounded p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0">Contact {{ $slot }}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger pickup-contact-remove">Remove contact</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-first-name">First name @if ($slotIsVisible) <span class="text-danger pickup-required-marker" aria-hidden="true">*</span><span class="visually-hidden">required</span>@else<span class="text-danger pickup-required-marker d-none" aria-hidden="true">*</span><span class="visually-hidden">required</span>@endif</label>
                            <input id="pickup-contact-{{ $slot }}-first-name" type="text" class="form-control" name="pickup_contacts[{{ $slot - 1 }}][first_name]" value="{{ $value('first_name') }}" maxlength="255" data-required-when-visible {{ $slotIsVisible ? 'required' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-last-name">Last name @if ($slotIsVisible) <span class="text-danger pickup-required-marker" aria-hidden="true">*</span><span class="visually-hidden">required</span>@else<span class="text-danger pickup-required-marker d-none" aria-hidden="true">*</span><span class="visually-hidden">required</span>@endif</label>
                            <input id="pickup-contact-{{ $slot }}-last-name" type="text" class="form-control" name="pickup_contacts[{{ $slot - 1 }}][last_name]" value="{{ $value('last_name') }}" maxlength="255" data-required-when-visible {{ $slotIsVisible ? 'required' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-relationship">Relationship @if ($slotIsVisible) <span class="text-danger pickup-required-marker" aria-hidden="true">*</span><span class="visually-hidden">required</span>@else<span class="text-danger pickup-required-marker d-none" aria-hidden="true">*</span><span class="visually-hidden">required</span>@endif</label>
                            <select id="pickup-contact-{{ $slot }}-relationship" class="form-select" name="pickup_contacts[{{ $slot - 1 }}][relationship]" data-required-when-visible {{ $slotIsVisible ? 'required' : '' }}>
                                <option value="">Select relationship</option>
                                @if (filled($relationshipValue) && ! array_key_exists($relationshipValue, $relationshipOptions))
                                    <option value="{{ $relationshipValue }}" selected>{{ $relationshipValue }}</option>
                                @endif
                                @foreach ($relationshipOptions as $relationship => $label)
                                    <option value="{{ $relationship }}" @selected((string) $relationshipValue === (string) $relationship)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-email">Email @if ($slotIsVisible) <span class="text-danger pickup-required-marker" aria-hidden="true">*</span><span class="visually-hidden">required</span>@else<span class="text-danger pickup-required-marker d-none" aria-hidden="true">*</span><span class="visually-hidden">required</span>@endif</label>
                            <input id="pickup-contact-{{ $slot }}-email" type="email" class="form-control" name="pickup_contacts[{{ $slot - 1 }}][email]" value="{{ $value('email') }}" maxlength="255" data-required-when-visible {{ $slotIsVisible ? 'required' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-phone">Phone @if ($slotIsVisible) <span class="text-danger pickup-required-marker" aria-hidden="true">*</span><span class="visually-hidden">required</span>@else<span class="text-danger pickup-required-marker d-none" aria-hidden="true">*</span><span class="visually-hidden">required</span>@endif</label>
                            <input id="pickup-contact-{{ $slot }}-phone" type="text" class="form-control" name="pickup_contacts[{{ $slot - 1 }}][phone]" value="{{ $value('phone') }}" maxlength="50" data-required-when-visible {{ $slotIsVisible ? 'required' : '' }}>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-address">Address @if ($slotIsVisible) <span class="text-danger pickup-required-marker" aria-hidden="true">*</span><span class="visually-hidden">required</span>@else<span class="text-danger pickup-required-marker d-none" aria-hidden="true">*</span><span class="visually-hidden">required</span>@endif</label>
                            <input id="pickup-contact-{{ $slot }}-address" type="text" class="form-control" name="pickup_contacts[{{ $slot - 1 }}][address]" value="{{ $value('address') }}" maxlength="255" data-required-when-visible {{ $slotIsVisible ? 'required' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pickup-contact-{{ $slot }}-image">Photo</label>
                            <input id="pickup-contact-{{ $slot }}-image" type="file" class="form-control" name="pickup_contacts[{{ $slot - 1 }}][image]" accept="image/*">
                            <input type="hidden" class="pickup-contact-remove-image-flag" name="pickup_contacts[{{ $slot - 1 }}][remove_image]" value="{{ $removeImage ? '1' : '0' }}">
                        </div>
                        <div class="col-md-6 pickup-contact-current-photo">
                            <span class="form-label d-block">Current photo</span>
                            @if ($contact?->image_path && ! $removeImage)
                                <div class="pickup-contact-preview">
                                    <img src="{{ asset('storage/'.$contact->image_path) }}" alt="Photo of {{ $contact->full_name }}" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: contain;">
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2 pickup-contact-photo-remove">Remove photo</button>
                                </div>
                            @else
                                <div class="pickup-contact-preview">
                                    <p class="text-muted small mb-0">No photo uploaded.</p>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2 pickup-contact-photo-remove d-none">Remove photo</button>
                                </div>
                            @endif
                        </div>
                        <div class="col-12 d-flex align-items-end gap-3 pb-2">
                            <div class="form-check">
                                <input type="hidden" name="pickup_contacts[{{ $slot - 1 }}][can_pick_up]" value="0">
                                <input id="pickup-contact-{{ $slot }}-can-pick-up" type="checkbox" class="form-check-input" name="pickup_contacts[{{ $slot - 1 }}][can_pick_up]" value="1" @checked($canPickUp)>
                                <label class="form-check-label" for="pickup-contact-{{ $slot }}-can-pick-up">Can pick up</label>
                            </div>
                            <div class="form-check">
                                <input type="hidden" name="pickup_contacts[{{ $slot - 1 }}][can_drop_off]" value="0">
                                <input id="pickup-contact-{{ $slot }}-can-drop-off" type="checkbox" class="form-check-input" name="pickup_contacts[{{ $slot - 1 }}][can_drop_off]" value="1" @checked($canDropOff)>
                                <label class="form-check-label" for="pickup-contact-{{ $slot }}-can-drop-off">Can drop off</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <button type="button" class="btn btn-outline-primary mt-3 pickup-contact-add{{ $visibleSlots >= 2 ? ' d-none' : '' }}">Add another contact</button>
@include('crud::fields.inc.wrapper_end')

<script>
    document.querySelectorAll('.pickup-contact-add').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('.form-group');
            const nextSlot = field?.querySelector('.pickup-contact-slot.d-none');

            if (!nextSlot) {
                button.classList.add('d-none');
                return;
            }

            nextSlot.classList.remove('d-none');
            nextSlot.querySelectorAll('[data-required-when-visible]').forEach((input) => {
                input.required = true;
            });
            nextSlot.querySelectorAll('.pickup-required-marker').forEach((marker) => {
                marker.classList.remove('d-none');
            });

            if (!field.querySelector('.pickup-contact-slot.d-none')) {
                button.classList.add('d-none');
            }
        });
    });

    document.querySelectorAll('.pickup-contact-remove').forEach((button) => {
        button.addEventListener('click', () => {
            const slot = button.closest('.pickup-contact-slot');
            if (!slot) {
                return;
            }

            const completeSlots = [...document.querySelectorAll('.pickup-contact-slot')]
                .filter((contactSlot) => {
                    const firstName = contactSlot.querySelector('input[name$="[first_name]"]')?.value.trim();
                    const lastName = contactSlot.querySelector('input[name$="[last_name]"]')?.value.trim();
                    return firstName && lastName;
                });

            const currentFirstName = slot.querySelector('input[name$="[first_name]"]')?.value.trim();
            const currentLastName = slot.querySelector('input[name$="[last_name]"]')?.value.trim();
            if (currentFirstName && currentLastName && completeSlots.length <= 1) {
                swal({
                    title: 'Contact required',
                    text: 'At least one pickup or drop-off contact is required.',
                    icon: 'warning',
                    button: 'OK',
                });
                return;
            }

            const removeContact = () => {
                slot.querySelectorAll('input:not([type="hidden"]), select').forEach((input) => {
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
                slot.querySelectorAll('[data-required-when-visible]').forEach((input) => {
                    input.required = false;
                });

                const preview = slot.querySelector('.pickup-contact-preview');
                if (preview) {
                    if (preview.dataset.objectUrl) {
                        URL.revokeObjectURL(preview.dataset.objectUrl);
                    }
                    preview.innerHTML = '<p class="text-muted small mb-0">No photo uploaded.</p>';
                    delete preview.dataset.objectUrl;
                }

                slot.classList.add('d-none');
                slot.closest('.form-group')?.querySelector('.pickup-contact-add')?.classList.remove('d-none');
            };

            swal({
                title: 'Remove contact?',
                text: 'This contact will be removed from this child after you save.',
                icon: 'warning',
                buttons: ['Cancel', 'Remove'],
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    removeContact();
                }
            });
        });
    });

    document.querySelectorAll('.pickup-contact-slot input[type="file"]').forEach((input) => {
        input.addEventListener('cancel', () => clearPhoto(input));
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const slot = input.closest('.pickup-contact-slot');
            const preview = slot?.querySelector('.pickup-contact-preview');

            if (!file) {
                clearPhoto(input);
                return;
            }

            if (!preview || !file.type.startsWith('image/')) {
                return;
            }

            if (preview.dataset.objectUrl) {
                URL.revokeObjectURL(preview.dataset.objectUrl);
            }

            const objectUrl = URL.createObjectURL(file);
            slot.querySelector('.pickup-contact-remove-image-flag').value = '0';
            preview.dataset.objectUrl = objectUrl;
            preview.innerHTML = '';

            const image = document.createElement('img');
            image.src = objectUrl;
            image.alt = `Selected photo for contact ${slot.dataset.slot}`;
            image.className = 'img-thumbnail';
            image.style.width = '200px';
            image.style.height = '200px';
            image.style.objectFit = 'contain';
            preview.appendChild(image);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-danger mt-2 pickup-contact-photo-remove';
            removeButton.textContent = 'Remove photo';
            preview.appendChild(removeButton);
            bindPhotoRemove(removeButton);
        });
    });

    function clearPhoto(input) {
        const slot = input.closest('.pickup-contact-slot');
        const preview = slot?.querySelector('.pickup-contact-preview');
        if (!slot || !preview) {
            return;
        }

        if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
            delete preview.dataset.objectUrl;
        }
        input.value = '';
        slot.querySelector('.pickup-contact-remove-image-flag').value = '1';
        preview.innerHTML = '<p class="text-muted small mb-0">No photo uploaded.</p>';
    }

    function bindPhotoRemove(button) {
        button.addEventListener('click', () => {
            const input = button.closest('.pickup-contact-slot')?.querySelector('input[type="file"]');
            if (input) {
                clearPhoto(input);
            }
        });
    }

    document.querySelectorAll('.pickup-contact-photo-remove').forEach(bindPhotoRemove);
</script>
