@extends('payroll.layout')

@section('payroll_content')
    @php
        $isAcc = backpack_user()->person->sacRole->code === \App\Models\Person::ROLE_ACCOUNTANT;
        $latestSubmission = $expense->events->where('action', 'submit')->sortByDesc('id')->first();
    @endphp
    <a href="{{ route('expenses.index') }}" class="d-inline-block mb-3"><i class="la la-arrow-left" aria-hidden="true"></i> Expenses</a>
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
        <div class="text-break"><h2 class="mb-1">{{ $expense->title }}</h2><div class="d-flex flex-wrap align-items-center gap-2">{{ $expense->reference }} @include('expenses.status', ['status' => $expense->status])</div></div>
        @if ($isAcc && $expense->editable())<a class="btn btn-primary" href="{{ route('expenses.edit', $expense) }}"><i class="la la-edit" aria-hidden="true"></i> Edit Expense</a>@endif
    </div>
    @if ($expense->return_reason)<div class="alert alert-warning">{{ $expense->return_reason }}</div>@endif
    @if ($expense->status !== 'returned' && filled($latestSubmission?->details['response'] ?? null))
        <div class="alert alert-info text-break">
            <div class="mb-2"><strong>Reason for Return</strong><br>{!! nl2br(e($latestSubmission->details['return_reason'] ?? '')) !!}</div>
            <div><strong>Accountant Response</strong><br>{!! nl2br(e($latestSubmission->details['response'])) !!}</div>
        </div>
    @endif
    <dl class="row mb-4">
        <dt class="col-sm-3">Amount (NGN)</dt><dd class="col-sm-9 fw-bold">{{ number_format($expense->amount_kobo / 100, 2) }}</dd>
        <dt class="col-sm-3">Expense Date</dt><dd class="col-sm-9">{{ $expense->expense_date->format('d M Y') }}</dd>
        <dt class="col-sm-3">Category</dt><dd class="col-sm-9 text-break">{{ $expense->category }}</dd>
        <dt class="col-sm-3">Supplier / Payee</dt><dd class="col-sm-9 text-break">{{ $expense->payee }}</dd>
        <dt class="col-sm-3">Description / Purpose</dt><dd class="col-sm-9 text-break">{!! nl2br(e($expense->description)) !!}</dd>
        <dt class="col-sm-3">Payee Bank</dt><dd class="col-sm-9 text-break">{{ $expense->bank_name ?: 'Not provided' }}<br>{{ $expense->account_name }}<br>{{ $expense->account_number }}</dd>
        @if ($expense->approved_at)<dt class="col-sm-3">Approved</dt><dd class="col-sm-9">{{ $expense->approved_at->format('d M Y H:i') }} by {{ $expense->approval_snapshot['ceo_name'] }}</dd>@endif
        @if ($expense->paid_at)<dt class="col-sm-3">Paid</dt><dd class="col-sm-9 text-break">{{ $expense->paid_at->format('d M Y') }} &middot; {{ $expense->payment_reference }}</dd>@endif
    </dl>
    <div class="d-flex flex-wrap gap-3 mb-4">
        @foreach (['invoice' => 'Invoice', 'receipt' => 'Purchase Receipt'] as $asset => $label)
            @if ($expense->getAttribute($asset.'_path'))<a class="btn btn-outline-secondary" href="{{ route('expenses.attachment', [$expense, $asset]) }}"><i class="la la-download" aria-hidden="true"></i> {{ $label }}</a>@endif
        @endforeach
        @if ($expense->approved())
            <a class="btn btn-outline-primary" href="{{ route('expenses.bank', $expense) }}"><i class="la la-download" aria-hidden="true"></i> Bank Payment Instruction</a>
            <a class="btn btn-outline-secondary" href="{{ route('expenses.bank', [$expense, 'inline' => 1]) }}" target="_blank" rel="noopener"><i class="la la-print" aria-hidden="true"></i> Open / Print</a>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-3 mb-4">
        @if ($isAcc && $expense->editable())
            <form action="{{ route('expenses.action', [$expense, 'submit']) }}" method="post" class="{{ $expense->status === 'returned' ? 'w-100' : '' }}" data-confirm="Submit this expense for approval? Details will be locked." data-confirm-title="Submit Expense?" data-confirm-button="Submit">
                @csrf
                @if ($expense->status === 'returned')
                    <label for="response" class="form-label">Response to Return</label>
                    <textarea class="form-control mb-2" id="response" name="response" rows="3" maxlength="2000" required>{{ old('response') }}</textarea>
                @endif
                <button class="btn btn-primary"><i class="la la-paper-plane" aria-hidden="true"></i> {{ $expense->status === 'returned' ? 'Respond & Resubmit' : 'Submit for Approval' }}</button>
            </form>
            @if ($expense->status === 'draft' && ! $expense->submitted_at)<form action="{{ route('expenses.destroy', $expense) }}" method="post" data-confirm="Delete this draft and its documents?" data-confirm-title="Delete Expense Draft?" data-confirm-button="Delete" data-confirm-danger="true">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="la la-trash" aria-hidden="true"></i> Delete Draft</button></form>@endif
        @endif
        @if (! $isAcc && $expense->status === 'submitted')
            <form action="{{ route('expenses.action', [$expense, 'approve']) }}" method="post" data-confirm="Approve this expense and its bank payment instruction?" data-confirm-title="Approve Expense?" data-confirm-button="Approve">@csrf<button class="btn btn-success"><i class="la la-check" aria-hidden="true"></i> Approve Expense</button></form>
        @endif
    </div>
    @if (! $isAcc && $expense->status === 'submitted')
        <form action="{{ route('expenses.action', [$expense, 'return']) }}" method="post" class="mb-4">@csrf<label for="reason" class="form-label">Reason for Return</label><textarea class="form-control mb-2" id="reason" name="reason" maxlength="2000" required>{{ old('reason') }}</textarea><button class="btn btn-outline-warning"><i class="la la-undo" aria-hidden="true"></i> Return to Accountant</button></form>
    @endif
    @if ($isAcc && $expense->status === 'approved')
        <form action="{{ route('expenses.action', [$expense, 'paid']) }}" method="post" class="row g-3 align-items-end mb-4" data-confirm="Confirm this expense payment has been completed?" data-confirm-title="Mark Expense Paid?" data-confirm-button="Mark Paid">@csrf
            <div class="col-md-5"><label for="payment_reference" class="form-label">Payment Reference</label><input class="form-control" id="payment_reference" name="payment_reference" maxlength="255" value="{{ old('payment_reference') }}" required></div>
            <div class="col-md-4"><label for="payment_date" class="form-label">Payment Date</label><input type="date" class="form-control" id="payment_date" name="payment_date" min="{{ $expense->approved_at->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required></div>
            <div class="col-md-3"><button class="btn btn-success"><i class="la la-check-circle" aria-hidden="true"></i> Mark Paid</button></div>
        </form>
    @endif
    @if ($isAcc && $expense->approved())
        <form action="{{ route('expenses.receipt', $expense) }}" method="post" enctype="multipart/form-data" class="row g-3 align-items-end mb-4">@csrf
            <div class="col-md-8"><label for="receipt" class="form-label">Purchase Receipt (Optional, PDF/JPG/PNG, max 10 MB)</label><input type="file" class="form-control" id="receipt" name="receipt" accept="application/pdf,image/jpeg,image/png" required></div>
            <div class="col-md-4"><button class="btn btn-outline-primary"><i class="la la-upload" aria-hidden="true"></i> {{ $expense->receipt_path ? 'Replace Receipt' : 'Upload Receipt' }}</button></div>
        </form>
    @endif
    <h3 class="h5 mt-4">Audit History</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Actor</th><th>Action</th><th>Details</th></tr></thead><tbody>
        @foreach ($expense->events->sortByDesc('id') as $event)<tr><td class="text-nowrap">{{ $event->created_at->format('d M Y H:i') }}</td><td>{{ $event->user?->name }}</td><td>{{ ucwords(str_replace('_', ' ', $event->action)) }}</td><td>@foreach ($event->details ?? [] as $key => $value)<div class="text-break">{{ ucwords(str_replace('_', ' ', $key)) }}: {{ $value }}</div>@endforeach</td></tr>@endforeach
    </tbody></table></div>
@endsection
