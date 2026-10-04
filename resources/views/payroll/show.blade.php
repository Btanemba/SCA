@extends('payroll.layout')

@section('payroll_content')
    @php
        $isAcc = backpack_user()->person->sacRole->code === \App\Models\Person::ROLE_ACCOUNTANT;
        $period = \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y');
        $latestSubmission = $payroll->events->where('action', 'submit')->sortByDesc('id')->first();
    @endphp
    <a href="{{ route('payroll.index') }}" class="d-inline-block mb-3"><i class="la la-arrow-left" aria-hidden="true"></i> Payroll</a>
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
        <div><h2 class="mb-1">{{ $period }}</h2><div>{{ $payroll->reference }} <span class="badge bg-secondary ms-2">{{ ucfirst($payroll->status) }}</span></div></div>
        @if ($isAcc && $payroll->editable())<a class="btn btn-primary" href="{{ route('payroll.entries.create', $payroll) }}"><i class="la la-plus" aria-hidden="true"></i> Add Salary</a>@endif
    </div>
    @if ($payroll->return_reason)<div class="alert alert-warning">{{ $payroll->return_reason }}</div>@endif
    @if ($payroll->status !== 'returned' && filled($latestSubmission?->details['response'] ?? null))
        <div class="alert alert-info text-break">
            <div class="mb-2"><strong>Reason for Return</strong><br>{!! nl2br(e($latestSubmission->details['return_reason'] ?? '')) !!}</div>
            <div><strong>Accountant Response</strong><br>{!! nl2br(e($latestSubmission->details['response'])) !!}</div>
        </div>
    @endif
    @if ($payroll->approved_at)<p>Approved {{ $payroll->approved_at->format('d M Y H:i') }} by {{ $payroll->approval_snapshot['ceo_name'] }}</p>@endif
    @if ($payroll->paid_at)<p>Paid {{ $payroll->paid_at->format('d M Y') }} &middot; {{ $payroll->payment_reference }}</p>@endif
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Employee</th><th>Bank / Account</th><th class="text-end">Gross (NGN)</th><th class="text-end">Deductions (NGN)</th><th class="text-end">Net (NGN)</th><th>Actions</th></tr></thead>
            <tbody>@forelse ($payroll->entries as $entry)
                <tr>
                    <td>{{ $entry->employee_name }}<details class="mt-1"><summary>Breakdown</summary><div>Basic: {{ number_format($entry->basic_kobo / 100, 2) }}</div>@foreach ($entry->allowances as $item)<div>{{ $item['label'] }}: +{{ number_format($item['kobo'] / 100, 2) }}</div>@endforeach @foreach ($entry->deductions as $item)<div>{{ $item['label'] }}: -{{ number_format($item['kobo'] / 100, 2) }}</div>@endforeach</details></td>
                    <td>{{ $entry->bank_name }}<br>{{ $entry->account_name }}<br>{{ $entry->account_number }}</td>
                    <td class="text-end">{{ number_format($entry->gross_kobo / 100, 2) }}</td><td class="text-end">{{ number_format($entry->deductions_kobo / 100, 2) }}</td><td class="text-end">{{ number_format($entry->net_kobo / 100, 2) }}</td>
                    <td><div class="d-flex gap-2">
                        @if ($isAcc && $payroll->editable())
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.entries.edit', [$payroll, $entry]) }}" title="Edit salary" aria-label="Edit salary"><i class="la la-edit" aria-hidden="true"></i></a>
                            <form action="{{ route('payroll.entries.destroy', [$payroll, $entry]) }}" method="post" data-confirm="Remove this salary entry?" data-confirm-title="Remove Salary?" data-confirm-button="Remove" data-confirm-danger="true">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Remove salary" aria-label="Remove salary"><i class="la la-trash" aria-hidden="true"></i></button></form>
                        @endif
                        @if ($payroll->approved())<a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.payslip', $entry) }}" title="Download payslip" aria-label="Download payslip"><i class="la la-download" aria-hidden="true"></i></a>@endif
                    </div></td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted">No salary entries.</td></tr>@endforelse</tbody>
            <tfoot><tr><th colspan="2">Totals</th><th class="text-end">{{ number_format($payroll->entries->sum('gross_kobo') / 100, 2) }}</th><th class="text-end">{{ number_format($payroll->entries->sum('deductions_kobo') / 100, 2) }}</th><th class="text-end">{{ number_format($payroll->entries->sum('net_kobo') / 100, 2) }}</th><th></th></tr></tfoot>
        </table>
    </div>
    <div class="d-flex flex-wrap gap-3 my-4">
        @if ($isAcc && $payroll->editable())
            <form action="{{ route('payroll.action', [$payroll, 'submit']) }}" method="post" class="{{ $payroll->status === 'returned' ? 'w-100' : '' }}" data-confirm="Submit this payroll to CEO? Amounts will be locked." data-confirm-title="Submit Payroll?" data-confirm-button="Submit">
                @csrf
                @if ($payroll->status === 'returned')
                    <label for="response" class="form-label">Response to Return</label>
                    <textarea class="form-control mb-2" id="response" name="response" rows="3" maxlength="2000" required>{{ old('response') }}</textarea>
                @endif
                <button class="btn btn-primary" @disabled($payroll->entries->isEmpty())><i class="la la-paper-plane" aria-hidden="true"></i> {{ $payroll->status === 'returned' ? 'Respond & Resubmit' : 'Submit for Approval' }}</button>
            </form>
            @if ($payroll->status === 'draft' && ! $payroll->submitted_at)<form action="{{ route('payroll.destroy', $payroll) }}" method="post" data-confirm="Delete this draft and all its entries?" data-confirm-title="Delete Payroll Draft?" data-confirm-button="Delete" data-confirm-danger="true">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="la la-trash" aria-hidden="true"></i> Delete Draft</button></form>@endif
        @endif
        @if (! $isAcc && $payroll->status === 'submitted')
            <form action="{{ route('payroll.action', [$payroll, 'approve']) }}" method="post" data-confirm="Approve this payroll and release staff payslips?" data-confirm-title="Approve Payroll?" data-confirm-button="Approve">@csrf<button class="btn btn-success"><i class="la la-check" aria-hidden="true"></i> Approve Monthly Payroll</button></form>
        @endif
        @if ($payroll->approved())
            <a class="btn btn-outline-primary" href="{{ route('payroll.bank', $payroll) }}"><i class="la la-download" aria-hidden="true"></i> Bank Letter &amp; Schedule</a>
            <a class="btn btn-outline-secondary" href="{{ route('payroll.bank', [$payroll, 'inline' => 1]) }}" target="_blank" rel="noopener"><i class="la la-print" aria-hidden="true"></i> Open / Print</a>
        @endif
    </div>
    @if (! $isAcc && $payroll->status === 'submitted')
        <form action="{{ route('payroll.action', [$payroll, 'return']) }}" method="post" class="mb-4">@csrf
            <label class="form-label" for="reason">Reason for Return</label><textarea class="form-control mb-2" name="reason" id="reason" maxlength="2000" required>{{ old('reason') }}</textarea>
            <button class="btn btn-outline-warning"><i class="la la-undo" aria-hidden="true"></i> Return to ACC</button>
        </form>
    @endif
    @if ($isAcc && $payroll->approved())
        @if ($payroll->approval_snapshot['bank_email'] ?? null)
            <form action="{{ route('payroll.email', $payroll) }}" method="post" class="mb-4" data-confirm="Email the approved payroll letter and bank details to the displayed recipient?" data-confirm-title="Email Bank?" data-confirm-button="Send Email">@csrf
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="confirm_recipient" id="confirm_recipient" value="{{ $payroll->approval_snapshot['bank_email'] }}" required><label class="form-check-label" for="confirm_recipient">Confirm recipient: {{ $payroll->approval_snapshot['bank_email'] }}</label></div>
                <button class="btn btn-outline-primary"><i class="la la-envelope" aria-hidden="true"></i> Email Bank</button>
            </form>
        @endif
        @if ($payroll->status === 'approved')
            <form action="{{ route('payroll.action', [$payroll, 'paid']) }}" method="post" class="row g-3 align-items-end mb-4" data-confirm="Confirm the bank has completed these salary payments?" data-confirm-title="Mark Payroll Paid?" data-confirm-button="Mark Paid">@csrf
                <div class="col-md-4"><label for="payment_reference" class="form-label">Payment Reference</label><input class="form-control" id="payment_reference" name="payment_reference" maxlength="255" value="{{ old('payment_reference') }}" required></div>
                <div class="col-md-3"><label for="payment_date" class="form-label">Payment Date</label><input type="date" class="form-control" id="payment_date" name="payment_date" min="{{ $payroll->approved_at->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required></div>
                <div class="col-md-3"><button class="btn btn-success"><i class="la la-check-circle" aria-hidden="true"></i> Mark Paid</button></div>
            </form>
        @endif
    @endif
    <h3 class="h5 mt-4">Audit History</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Actor</th><th>Action</th><th>Details</th></tr></thead><tbody>
        @foreach ($payroll->events->sortByDesc('id') as $event)<tr><td>{{ $event->created_at->format('d M Y H:i') }}</td><td>{{ $event->user?->name }}</td><td>{{ ucwords(str_replace('_', ' ', $event->action)) }}</td><td>@foreach ($event->details ?? [] as $key => $value)<div>{{ ucwords(str_replace('_', ' ', $key)) }}: {{ $value }}</div>@endforeach</td></tr>@endforeach
    </tbody></table></div>
@endsection
