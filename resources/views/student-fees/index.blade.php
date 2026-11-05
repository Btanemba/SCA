@extends('payroll.layout')

@section('payroll_content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="mb-0">Child Fees</h2>
    </div>

    <form action="{{ route('student-fees.index') }}" method="get" class="row g-3 align-items-end mb-4">
        <div class="col-md-3"><label for="month-filter" class="form-label">Billing month</label><input class="form-control" id="month-filter" name="month" type="month" value="{{ $month }}"></div>
        <div class="col-md-3"><label for="status-filter" class="form-label">Payment status</label><select class="form-select" id="status-filter" name="status"><option value="">All students</option>@foreach (['not_charged' => 'Not charged', 'unpaid' => 'Unpaid', 'part_paid' => 'Part-paid', 'paid' => 'Paid'] as $status => $label)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="search" class="form-label">Search child or fee</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" maxlength="255"></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary"><i class="la la-search" aria-hidden="true"></i> Filter</button><a href="{{ route('student-fees.index') }}" class="btn btn-outline-secondary" title="Clear filters" aria-label="Clear filters"><i class="la la-times" aria-hidden="true"></i></a></div>
    </form>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <h3 class="h5 mb-0">All Students - {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h3>
        @if ($fees->isNotEmpty())
            <form id="bulk-student-fees-form" action="{{ route('student-fees.charges.all') }}" method="post"
                data-confirm="Add only the fees selected for students in {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}? Students without a selected fee will not be charged."
                data-confirm-title="Add All Monthly Fees"
                data-confirm-button="Add Charges"
                data-confirm-danger="true">
                @csrf
                <input type="hidden" name="billing_month" value="{{ $month }}">
                <button class="btn btn-primary"><i class="la la-users" aria-hidden="true"></i> Add selected fees</button>
            </form>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Child</th><th>Monthly fees</th><th class="text-end">Due (NGN)</th><th class="text-end">Paid (NGN)</th><th class="text-end">Balance (NGN)</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->full_name }}<div class="small text-muted">{{ $student->student_id }}</div></td>
                        <td>
                            @forelse ($student->monthCharges as $monthCharge)
                                <div><a href="#charge-{{ $monthCharge->id }}">{{ $monthCharge->fee_name }}</a> <span class="text-muted">NGN {{ number_format($monthCharge->amount_due_kobo / 100, 2) }}</span></div>
                            @empty
                                <span class="text-muted">No charge recorded</span>
                            @endforelse
                        </td>
                        <td class="text-end text-nowrap">{{ $student->month_due_kobo ? number_format($student->month_due_kobo / 100, 2) : '—' }}</td>
                        <td class="text-end text-nowrap">{{ $student->month_due_kobo ? number_format($student->month_paid_kobo / 100, 2) : '—' }}</td>
                        <td class="text-end text-nowrap">{{ $student->month_due_kobo ? number_format($student->month_balance_kobo / 100, 2) : '—' }}</td>
                        <td><span class="badge text-bg-{{ $student->month_payment_status === 'paid' ? 'success' : ($student->month_payment_status === 'part_paid' ? 'warning' : ($student->month_payment_status === 'unpaid' ? 'danger' : 'secondary')) }}">{{ $student->month_payment_status === 'part_paid' ? 'Part-paid' : ($student->month_payment_status === 'not_charged' ? 'Not charged' : ucfirst($student->month_payment_status)) }}</span></td>
                        <td>
                            @if ($student->availableMonthFees->isNotEmpty())
                                <form action="{{ route('student-fees.charges') }}" method="post" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                    <input type="hidden" name="billing_month" value="{{ $month }}">
                                    <select class="form-select form-select-sm" name="academy_fee_id" data-bulk-student-id="{{ $student->id }}" aria-label="Monthly fee for {{ $student->full_name }}" required>
                                        <option value="">Choose fee</option>
                                        @foreach ($student->availableMonthFees as $fee)
                                            <option value="{{ $fee->id }}">{{ $fee->fee_name }} (NGN {{ number_format($fee->amount) }})</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary text-nowrap">Add fee</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No students found for these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h3 class="h5 mt-4 mb-3">Fee Payments</h3>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Child</th><th>Fee</th><th>Month</th><th class="text-end">Due (NGN)</th><th class="text-end">Paid (NGN)</th><th class="text-end">Balance (NGN)</th><th>Status</th><th>Payments / Record Payment</th></tr></thead>
            <tbody>
                @forelse ($charges as $charge)
                    <tr id="charge-{{ $charge->id }}">
                        <td>{{ $charge->child->full_name }}<div class="small text-muted">{{ $charge->child->student_id }}</div></td>
                        <td>{{ $charge->fee_name }}</td>
                        <td class="text-nowrap">{{ $charge->billing_month->format('F Y') }}</td>
                        <td class="text-end text-nowrap">{{ number_format($charge->amount_due_kobo / 100, 2) }}</td>
                        <td class="text-end text-nowrap">{{ number_format($charge->paid_amount_kobo / 100, 2) }}</td>
                        <td class="text-end text-nowrap">{{ number_format($charge->balance_kobo / 100, 2) }}</td>
                        <td><span class="badge text-bg-{{ $charge->payment_status === 'paid' ? 'success' : ($charge->payment_status === 'part_paid' ? 'warning' : 'danger') }}">{{ $charge->payment_status === 'part_paid' ? 'Part-paid' : ucfirst($charge->payment_status) }}</span></td>
                        <td style="min-width: 290px">
                            @foreach ($charge->payments as $payment)
                                <div class="small mb-2">NGN {{ number_format($payment->amount_kobo / 100, 2) }} - {{ $payment->paid_at->format('d M Y') }} - {{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}@if ($payment->reference) <span class="text-muted">({{ $payment->reference }})</span>@endif</div>
                            @endforeach
                            @if ($charge->balance_kobo > 0)
                                <form action="{{ route('student-fees.payments', $charge) }}" method="post" class="d-flex flex-wrap gap-2 mt-2">
                                    @csrf
                                    <input class="form-control form-control-sm" name="amount" type="number" min="0.01" max="{{ number_format($charge->balance_kobo / 100, 2, '.', '') }}" step="0.01" placeholder="Amount" aria-label="Payment amount for {{ $charge->child->full_name }}" required>
                                    <input class="form-control form-control-sm" name="paid_at" type="date" value="{{ now()->format('Y-m-d') }}" aria-label="Payment date" required>
                                    <select class="form-select form-select-sm" name="payment_method" aria-label="Payment method" required><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="pos">POS</option><option value="other">Other</option></select>
                                    <input class="form-control form-control-sm" name="reference" maxlength="255" placeholder="Reference (optional)" aria-label="Payment reference">
                                    <button class="btn btn-sm btn-outline-primary">Record Payment</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No fee charges found for these students this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $charges->links() }}
@endsection

@push('after_scripts')
    <script>
        (() => {
            const form = document.getElementById('bulk-student-fees-form');
            if (!form) return;

            form.addEventListener('submit', event => {
                form.querySelectorAll('[data-bulk-selection]').forEach(input => input.remove());
                const selections = Array.from(document.querySelectorAll('[data-bulk-student-id]'))
                    .filter(select => select.value !== '');

                if (selections.length === 0) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    swal({ title: 'No fees selected', text: 'Choose a monthly fee for at least one student first.', icon: 'warning' });
                    return;
                }

                selections.forEach(select => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `fees[${select.dataset.bulkStudentId}]`;
                    input.value = select.value;
                    input.dataset.bulkSelection = '';
                    form.appendChild(input);
                });
            });
        })();
    </script>
@endpush
