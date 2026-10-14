<article class="management-card">
    @if ($member->person->image_path)
        <img
            class="management-photo"
            src="{{ asset('storage/'.$member->person->image_path) }}"
            alt="{{ $member->person->full_name }}"
            loading="lazy"
        >
    @else
        <div class="management-photo-placeholder" aria-hidden="true">{{ strtoupper(substr($member->person->first_name, 0, 1).substr($member->person->last_name, 0, 1)) }}</div>
    @endif
    <div class="management-card-copy">
        <h3>{{ $member->person->full_name }}</h3>
        <p class="management-title">{{ $member->person->Occupation ?: 'Staff member' }}</p>
        @if ($member->description)
            <p class="management-description">{!! nl2br(e($member->description)) !!}</p>
        @endif
    </div>
</article>
