<article class="management-card{{ $featured ? ' management-featured' : '' }}">
    @if ($member->image_path)
        <img
            class="management-photo"
            src="{{ asset('storage/'.$member->image_path) }}"
            alt="{{ $member->full_name }}"
            loading="lazy"
        >
    @else
        <div class="management-photo-placeholder" aria-hidden="true">{{ strtoupper(substr($member->first_name, 0, 1).substr($member->last_name, 0, 1)) }}</div>
    @endif
    <div class="management-card-copy">
        <h3>{{ $member->full_name }}</h3>
        <p class="management-title">{{ $member->title }}</p>
        @if ($member->description)
            <p class="management-description">{!! nl2br(e($member->description)) !!}</p>
        @endif
    </div>
</article>
