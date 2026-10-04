@extends('payroll.layout')

@section('payroll_content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="mb-0">Expenses</h2>
        @if (backpack_user()->person->sacRole->code === \App\Models\Person::ROLE_ACCOUNTANT)
            <a class="btn btn-primary" href="{{ route('expenses.create') }}"><i class="la la-plus" aria-hidden="true"></i> New Expense</a>
        @endif
    </div>
    <form action="{{ route('expenses.index') }}" method="get" class="row g-3 align-items-end mb-4">
        <div class="col-md-4"><label for="search" class="form-label">Search</label><input class="form-control" id="search" name="search" maxlength="255" value="{{ request('search') }}" placeholder="Reference, title, category or payee"></div>
        <div class="col-md-3"><label for="status" class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach (['draft' => 'Draft - Not Submitted', 'returned' => 'Returned - Needs Changes', 'submitted' => 'Awaiting Approval', 'approved' => 'Approved - Awaiting Payment', 'paid' => 'Paid'] as $status => $label)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label for="paid-month" class="form-label">Payment month</label><input class="form-control" id="paid-month" name="paid_month" type="month" value="{{ request('paid_month') }}"></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary"><i class="la la-search" aria-hidden="true"></i> Search</button><a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary" title="Clear filters" aria-label="Clear filters"><i class="la la-times" aria-hidden="true"></i></a></div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Reference / Expense</th><th>Date</th><th>Category</th><th>Payee</th><th class="text-end">Amount (NGN)</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse ($expenses as $expense)
                <tr><td><a href="{{ route('expenses.show', $expense) }}">{{ $expense->reference }}</a><div class="text-break">{{ $expense->title }}</div></td><td class="text-nowrap">{{ $expense->expense_date->format('d M Y') }}</td><td>{{ $expense->category }}</td><td>{{ $expense->payee }}</td><td class="text-end text-nowrap">{{ number_format($expense->amount_kobo / 100, 2) }}</td><td>@include('expenses.status', ['status' => $expense->status])</td><td><a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-primary" title="View expense" aria-label="View expense"><i class="la la-eye" aria-hidden="true"></i></a></td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted">No expenses found.</td></tr>@endforelse</tbody>
        </table>
    </div>
    {{ $expenses->links() }}
@endsection
