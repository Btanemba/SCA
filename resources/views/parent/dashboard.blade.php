@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@error('absence_date')
    <div class="alert alert-danger" role="alert">{{ $message }}</div>
@enderror
@error('reason')
    <div class="alert alert-danger" role="alert">{{ $message }}</div>
@enderror

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">My children</h1>
        <div class="text-muted">{{ now()->format('l, j F Y') }}</div>
    </div>
</div>

@forelse ($parentChildren as $child)
    @php
        $attendance = $child->attendances->first();
        $todayAbsence = $child->absenceNotices->first(fn ($notice) => $notice->absence_date->format('Y-m-d') === $parentToday);
    @endphp
    <section class="card mb-3" aria-label="{{ $child->full_name }}">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    @if ($child->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($child->image_path))
                        <img src="{{ route('parent.children.photo', $child->id) }}" width="56" height="56" class="rounded" style="object-fit: cover; flex-shrink: 0;" alt="Photo of {{ $child->full_name }}">
                    @else
                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted" style="width: 56px; height: 56px; flex-shrink: 0;" aria-label="No photo available"><i class="la la-user" aria-hidden="true"></i></span>
                    @endif
                    <div><h2 class="h4 mb-0">{{ $child->full_name }}</h2><span class="text-muted small">{{ $child->student_id }}</span></div>
                </div>
                <a class="btn btn-outline-primary" href="{{ route('parent.children.show', $child->id) }}">View child <i class="la la-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="row g-3">
                <div class="col-6 col-lg-3"><div class="text-muted small">Today</div><strong>{{ $attendance?->picked_up_at ? 'Picked up' : ($attendance?->dropped_off_at ? 'At school' : ($todayAbsence ? 'Absence reported' : 'No attendance recorded')) }}</strong></div>
                <div class="col-6 col-lg-3"><div class="text-muted small">{{ $parentCurrentMonth->format('F') }} balance</div><strong>{{ $child->has_current_fees ? 'NGN '.number_format($child->current_fee_balance_kobo / 100, 2) : 'Not charged' }}</strong></div>
                <div class="col-6 col-lg-3"><div class="text-muted small">{{ $parentNextMonth->format('F') }} balance</div><strong>{{ $child->has_next_fees ? 'NGN '.number_format($child->next_fee_balance_kobo / 100, 2) : 'Not available' }}</strong></div>
                <div class="col-6 col-lg-3"><div class="text-muted small">Wallet credit</div><strong>NGN {{ number_format($child->wallet_balance_kobo / 100, 2) }}</strong></div>
            </div>
            @if ($child->absenceNotices->isNotEmpty())
                <div class="border-top mt-3 pt-3">
                    @foreach ($child->absenceNotices as $notice)
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span>Reported absence: {{ $notice->absence_date->format('d M Y') }}@if (filled($notice->reason))<span class="d-block text-muted" style="white-space: pre-wrap; overflow-wrap: anywhere;">Reason: {{ $notice->reason }}</span>@endif</span>
                            <form action="{{ route('parent.children.absences.cancel', [$child->id, $notice->id]) }}" method="post" onsubmit="return confirm('Cancel this absence notice?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-secondary" aria-label="Cancel absence for {{ $child->full_name }} on {{ $notice->absence_date->format('d M Y') }}">Cancel</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
            <details class="mt-3">
                <summary class="btn btn-outline-primary d-inline-flex align-items-center gap-1"><i class="la la-calendar-plus" aria-hidden="true"></i> Report an absence</summary>
                <form action="{{ route('parent.children.absences.store', $child->id) }}" method="post" class="d-flex flex-wrap align-items-end gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="absence_child_id" value="{{ $child->id }}">
                    <div><label class="form-label small mb-1" for="absence-date-{{ $child->id }}">Absence date</label><input id="absence-date-{{ $child->id }}" class="form-control form-control-sm" type="date" name="absence_date" min="{{ $parentToday }}" value="{{ (string) old('absence_child_id') === (string) $child->id ? old('absence_date', $parentToday) : $parentToday }}" required></div>
                    <div class="flex-grow-1" style="min-width: 180px;"><label class="form-label small mb-1" for="absence-reason-{{ $child->id }}">Reason (optional)</label><textarea id="absence-reason-{{ $child->id }}" class="form-control form-control-sm" name="reason" rows="2" maxlength="500">{{ (string) old('absence_child_id') === (string) $child->id ? old('reason') : '' }}</textarea></div>
                    <button class="btn btn-sm btn-outline-primary">Report absence</button>
                </form>
            </details>
        </div>
    </section>
@empty
    <div class="alert alert-secondary">No children are linked to your account. Please contact the academy.</div>
@endforelse

<section class="mt-4" aria-labelledby="parent-events-heading">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
        <h2 class="h4 mb-0" id="parent-events-heading">Upcoming events</h2>
        <a href="{{ route('events.index') }}">All events</a>
    </div>
    @forelse ($parentEvents as $event)
        <div class="border-bottom py-2"><strong>{{ $event->title }}</strong><div class="small text-muted">{{ $event->starts_at->format('d M Y, g:i A') }}@if ($event->location) - {{ $event->location }}@endif</div></div>
    @empty
        <p class="text-muted">No upcoming events.</p>
    @endforelse
</section>
