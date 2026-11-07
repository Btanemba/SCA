@extends(backpack_view('blank'))

@section('title', $child->full_name)

@push('after_styles')
    <style>
        @media (max-width: 991.98px) {
            .parent-child-page, .parent-child-page .table-responsive { min-width: 0; max-width: 100%; }
            .parent-child-page .parent-child-name, .parent-child-page .parent-cell-value { min-width: 0; overflow-wrap: anywhere; }
            .parent-child-page .parent-responsive-table, .parent-child-page .parent-responsive-table tbody, .parent-child-page .parent-responsive-table tfoot { display: block; width: 100%; }
            .parent-child-page .parent-responsive-table thead { display: none; }
            .parent-child-page .parent-responsive-table tr { display: block; padding: 8px 0; border-bottom: 1px solid #dfe7e2; }
            .parent-child-page .parent-responsive-table td { display: grid; grid-template-columns: minmax(0, 7rem) minmax(0, 1fr); gap: 12px; align-items: start; width: 100%; border: 0; padding: 6px 8px; text-align: left !important; white-space: normal !important; }
            .parent-child-page .parent-responsive-table td[data-label]::before { content: attr(data-label); color: #52635d; font-size: 0.875rem; font-weight: 600; }
            .parent-child-page .parent-responsive-table td[colspan] { display: block; }
            .parent-child-page .parent-responsive-table .parent-table-spacer { display: none; }
            .parent-child-page .parent-fee-controls, .parent-child-page .parent-fee-controls form, .parent-child-page .parent-fee-controls form > div { min-width: 0; max-width: 100%; }
            .parent-child-page .parent-fee-controls input { min-width: 0; max-width: 100%; }
        }
    </style>
@endpush

@section('content')
    <div class="parent-child-page">
    <div class="mb-3"><a href="{{ backpack_url('dashboard') }}"><i class="la la-arrow-left" aria-hidden="true"></i> My children</a></div>
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        @if ($child->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($child->image_path))
            <img src="{{ route('parent.children.photo', $child->id) }}" width="64" height="64" class="rounded" style="object-fit: cover; flex-shrink: 0;" alt="Photo of {{ $child->full_name }}">
        @else
            <span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted" style="width: 64px; height: 64px; flex-shrink: 0;" aria-label="No photo available"><i class="la la-user" aria-hidden="true"></i></span>
        @endif
        <div class="parent-child-name">
            <h1 class="h2 mb-1">{{ $child->full_name }}</h1>
            <div class="text-muted">{{ $child->student_id }}@if ($child->date_of_birth) &middot; Born {{ $child->date_of_birth->format('d M Y') }}@endif</div>
        </div>
    </div>

    <section class="mb-5" aria-labelledby="fees-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <h2 class="h4 mb-0" id="fees-heading">Fees and payments</h2>
            <div class="parent-fee-controls d-flex flex-wrap align-items-end gap-2">
                <form action="{{ route('parent.children.show', $child->id) }}" method="get" class="d-flex align-items-end gap-2">
                    <div><label class="form-label small mb-1" for="parent-fee-month">Billing month</label><input id="parent-fee-month" class="form-control form-control-sm" type="month" name="month" value="{{ $month }}" required></div>
                    <button class="btn btn-sm btn-outline-primary">Show</button>
                </form>
                @if ($charges->isNotEmpty())
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('parent.children.invoice', ['student' => $child->id, 'month' => $month]) }}"><i class="la la-download" aria-hidden="true"></i> Download invoice</a>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="parent-responsive-table table table-striped align-middle">
                <thead><tr><th>Fee</th><th class="text-end">Due (NGN)</th><th class="text-end">Paid (NGN)</th><th class="text-end">Balance (NGN)</th><th>Payments</th></tr></thead>
                <tbody>
                    @forelse ($charges as $charge)
                        <tr>
                            <td data-label="Fee"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Fee: </span>{{ $charge->fee_name }}</span></td>
                            <td class="text-end text-nowrap" data-label="Due (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Due (NGN): </span>{{ number_format($charge->amount_due_kobo / 100, 2) }}</span></td>
                            <td class="text-end text-nowrap" data-label="Paid (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Paid (NGN): </span>{{ number_format($charge->paid_amount_kobo / 100, 2) }}</span></td>
                            <td class="text-end text-nowrap" data-label="Balance (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Balance (NGN): </span>{{ number_format($charge->balance_kobo / 100, 2) }}</span></td>
                            <td data-label="Payments"><div class="parent-cell-value"><span class="visually-hidden d-lg-none">Payments: </span>
                                @forelse ($charge->payments as $payment)
                                    <div class="mb-2">NGN {{ number_format($payment->amount_kobo / 100, 2) }} &middot; {{ $payment->paid_at->format('d M Y') }} &middot; {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                        @if ($payment->payment_receipt_id)<a class="btn btn-sm btn-outline-secondary ms-1" href="{{ route('parent.receipts.download', $payment->payment_receipt_id) }}"><i class="la la-download" aria-hidden="true"></i> Receipt</a>@endif
                                    </div>
                                @empty
                                    <span class="text-muted">No payments</span>
                                @endforelse
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No fees recorded for this month.</td></tr>
                    @endforelse
                </tbody>
                @if ($charges->isNotEmpty())
                    <tfoot><tr class="fw-bold"><td data-label="Summary"><span class="parent-cell-value">Total</span></td><td class="text-end text-nowrap" data-label="Due (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Due (NGN): </span>{{ number_format($charges->sum('amount_due_kobo') / 100, 2) }}</span></td><td class="text-end text-nowrap" data-label="Paid (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Paid (NGN): </span>{{ number_format($charges->sum(fn ($charge) => $charge->paid_amount_kobo) / 100, 2) }}</span></td><td class="text-end text-nowrap" data-label="Balance (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Balance (NGN): </span>{{ number_format($charges->sum(fn ($charge) => $charge->balance_kobo) / 100, 2) }}</span></td><td class="parent-table-spacer"></td></tr></tfoot>
                @endif
            </table>
        </div>
    </section>

    <section class="mb-5" aria-labelledby="wallet-heading">
        <h2 class="h4" id="wallet-heading">Wallet credit</h2>
        <div class="h5">NGN {{ number_format($walletBalance / 100, 2) }}</div>
        @if ($walletTransactions->isNotEmpty())
            <div class="table-responsive"><table class="parent-responsive-table table table-striped align-middle">
                <thead><tr><th>Date</th><th>Transaction</th><th class="text-end">Amount (NGN)</th></tr></thead>
                <tbody>@foreach ($walletTransactions as $transaction)
                    <tr><td data-label="Date"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Date: </span>{{ ($transaction->receipt?->paid_at ?? $transaction->created_at)->format('d M Y') }}</span></td><td data-label="Transaction"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Transaction: </span>{{ $transaction->type === 'credit' ? 'Credit' : 'Applied to fee' }}@if ($transaction->feePayment?->charge) - {{ $transaction->feePayment->charge->fee_name }} ({{ $transaction->feePayment->charge->billing_month->format('M Y') }})@endif</span></td><td class="text-end text-nowrap" data-label="Amount (NGN)"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Amount (NGN): </span>{{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount_kobo / 100, 2) }}</span></td></tr>
                @endforeach</tbody>
            </table></div>
        @else
            <p class="text-muted">No wallet transactions.</p>
        @endif
    </section>

    <section class="mb-5" aria-labelledby="attendance-heading">
        <h2 class="h4" id="attendance-heading">Recent attendance</h2>
        <div class="table-responsive"><table class="parent-responsive-table table table-striped align-middle">
            <thead><tr><th>Date</th><th>Drop-off</th><th>Pickup</th></tr></thead>
            <tbody>@forelse ($attendances as $attendance)
                <tr><td data-label="Date"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Date: </span>{{ $attendance->attendance_date->format('d M Y') }}</span></td><td data-label="Drop-off"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Drop-off: </span>{{ $attendance->dropped_off_at?->format('g:i A') ?? '-' }}@if ($attendance->dropped_off_by_name) &middot; {{ $attendance->dropped_off_by_name }}@endif</span></td><td data-label="Pickup"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Pickup: </span>{{ $attendance->picked_up_at?->format('g:i A') ?? '-' }}@if ($attendance->picked_up_by_name) &middot; {{ $attendance->picked_up_by_name }}@endif</span></td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No attendance recorded.</td></tr>
            @endforelse</tbody>
        </table></div>
    </section>

    <section aria-labelledby="pickup-heading">
        <h2 class="h4" id="pickup-heading">Authorized contacts</h2>
        <div class="table-responsive"><table class="parent-responsive-table table table-striped align-middle">
            <thead><tr><th>Name</th><th>Relationship</th><th>Authorization</th></tr></thead>
            <tbody>@forelse ($pickupContacts->filter(fn ($contact) => $contact->pivot->can_pick_up || $contact->pivot->can_drop_off) as $contact)
                <tr><td data-label="Name"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Name: </span>{{ $contact->full_name }}</span></td><td data-label="Relationship"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Relationship: </span>{{ $contact->pivot->relationship ?: '-' }}</span></td><td data-label="Authorization"><span class="parent-cell-value"><span class="visually-hidden d-lg-none">Authorization: </span>{{ $contact->pivot->can_drop_off ? 'Drop-off' : '' }}{{ $contact->pivot->can_drop_off && $contact->pivot->can_pick_up ? ', ' : '' }}{{ $contact->pivot->can_pick_up ? 'Pickup' : '' }}</span></td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No authorized contacts recorded.</td></tr>
            @endforelse</tbody>
        </table></div>
    </section>
    </div>
@endsection
