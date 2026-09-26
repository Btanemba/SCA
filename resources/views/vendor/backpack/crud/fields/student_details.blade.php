@php
    $details = [
        'Student ID' => $student->student_id,
        'First name' => $student->first_name,
        'Last name' => $student->last_name,
        'Gender' => $student->gender,
        'Date of birth' => $student->date_of_birth?->format('F j, Y'),
        'Address' => $student->address_line_1,
        'City' => $student->city,
        'State / province' => $student->state,
        'Postal code' => $student->postal_code,
        'Country' => $student->country,
    ];
    $photoDisk = \Illuminate\Support\Facades\Storage::disk('public');
    $photoExists = filled($student->image_path) && $photoDisk->exists($student->image_path);
@endphp

<div class="border rounded p-3 bg-light" aria-label="Student details">
    <div class="row g-3 align-items-start">
        <div class="col-auto">
            @if ($photoExists)
                <img
                    src="{{ $photoDisk->url($student->image_path) }}"
                    alt="Photo of {{ $student->full_name }}"
                    class="img-thumbnail"
                    style="width: 128px; height: 144px; object-fit: contain;"
                >
            @else
                <div class="border rounded bg-white text-muted d-flex align-items-center justify-content-center text-center p-2" style="width: 128px; height: 144px;">
                    No photo uploaded
                </div>
            @endif
        </div>
        <div class="col">
            <dl class="row mb-0">
                @foreach ($details as $label => $value)
                    @if (filled($value))
                        <dt class="col-sm-3">{{ $label }}</dt>
                        <dd class="col-sm-9">{{ $value }}</dd>
                    @endif
                @endforeach
            </dl>
        </div>
    </div>
</div>
