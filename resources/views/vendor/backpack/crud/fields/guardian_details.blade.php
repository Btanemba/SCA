@php
    $photoDisk = \Illuminate\Support\Facades\Storage::disk('public');
@endphp

@forelse ($entry->guardians as $guardian)
    @php
        $sections = [
            'Personal information' => [
                'columns' => 'col-md-6 col-lg-4',
                'details' => [
                    'First name' => $guardian->first_name,
                    'Last name' => $guardian->last_name,
                    'Gender' => $guardian->gender,
                    'Date of birth' => $guardian->date_of_birth?->format('F j, Y'),
                    'Occupation' => $guardian->Occupation,
                    'Nationality' => $guardian->nationality,
                    'State of origin' => $guardian->state_of_origin,
                    'Local government area' => $guardian->lga,
                ],
            ],
            'Contact information' => [
                'columns' => 'col-md-6',
                'details' => [
                    'Email' => $guardian->email,
                    'Alternative email' => $guardian->alt_email,
                    'Phone' => $guardian->phone,
                    'Alternative phone' => $guardian->alt_phone,
                ],
            ],
            'Address' => [
                'columns' => 'col-md-6 col-lg-4',
                'details' => [
                    'Home address' => $guardian->address_line_1,
                    'City' => $guardian->city,
                    'State / province' => $guardian->state,
                    'Postal code' => $guardian->postal_code,
                    'Country' => $guardian->country,
                ],
            ],
        ];
        $photoExists = filled($guardian->image_path) && $photoDisk->exists($guardian->image_path);
    @endphp

    <section class="border rounded p-3 mb-3" aria-label="{{ $guardian->full_name }}">
        <div class="d-flex align-items-start gap-3 mb-4">
            @if ($photoExists)
                <img
                    src="{{ $photoDisk->url($guardian->image_path) }}"
                    alt="Photo of {{ $guardian->full_name }}"
                    class="img-thumbnail"
                    style="width: 96px; height: 112px; object-fit: contain;"
                >
            @endif
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <h5 class="mb-0">{{ $guardian->full_name }}</h5>
                    @if ($guardian->pivot->is_primary_contact)
                        <span class="badge rounded-pill bg-dark text-white fs-6 px-3 py-2">Primary contact</span>
                    @endif
                </div>
                <p class="text-muted mb-0">
                    Relationship: {{ filled($guardian->pivot->relationship) ? $guardian->pivot->relationship : 'Not provided' }}
                </p>
            </div>
        </div>

        @foreach ($sections as $heading => $section)
            <h6 class="mb-3">{{ $heading }}</h6>
            <dl class="row g-3 mb-4">
                @foreach ($section['details'] as $label => $value)
                    <div class="{{ $section['columns'] }}">
                        <dt class="small text-muted fw-normal mb-1">{{ $label }}</dt>
                        <dd class="form-control bg-light mb-0">{{ filled($value) ? $value : 'Not provided' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endforeach
    </section>
@empty
    <p class="text-muted">No parents or guardians are linked to this student.</p>
@endforelse
