@php
    [$label, $foreground, $background, $icon] = match ($status) {
        'draft' => ['Draft - Not Submitted', '#374151', '#e5e7eb', 'la-edit'],
        'returned' => ['Returned - Needs Changes', '#8a2c0d', '#fff0d6', 'la-undo'],
        'submitted' => ['Awaiting Approval', '#075985', '#e0f2fe', 'la-clock'],
        'approved' => ['Approved - Awaiting Payment', '#166534', '#dcfce7', 'la-check'],
        'paid' => ['Paid', '#115e59', '#ccfbf1', 'la-check-circle'],
        default => [ucfirst($status ?: 'Unknown'), '#374151', '#e5e7eb', 'la-question-circle'],
    };
@endphp
<span class="d-inline-flex align-items-center gap-1 px-2 py-1 fw-semibold" style="color: {{ $foreground }}; background-color: {{ $background }}; border-radius: 4px; font-size: 12px; line-height: 1.4; text-align: left;">
    <i class="la {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
</span>
