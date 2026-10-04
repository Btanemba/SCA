@extends(backpack_view('blank'))

@php
    $currentRoleCode = backpack_user()?->person?->sacRole?->code;
    $isSecurityUser = $currentRoleCode === \App\Models\Person::ROLE_SECURITY;
    $isAccountantUser = $currentRoleCode === \App\Models\Person::ROLE_ACCOUNTANT;

    if ($isSecurityUser) {
        $widgets['before_content'] = [];
    } elseif ($isAccountantUser) {
        $widgets['before_content'] = [];
    } elseif (backpack_theme_config('show_getting_started')) {
        $widgets['before_content'][] = [
            'type'        => 'view',
            'view'        => backpack_view('inc.getting_started'),
        ];
    }
@endphp

@section('title', $isAccountantUser ? 'Finance overview' : trans('backpack::base.dashboard'))

@section('content')
@if ($isSecurityUser)
    @include('security.attendance_content')
@elseif ($isAccountantUser)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Finance overview</h1>
            <p class="text-muted mb-0">{{ now()->format('F Y') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('expenses.create') }}"><i class="la la-plus" aria-hidden="true"></i> New expense</a>
            <a class="btn btn-outline-primary" href="{{ route('payroll.index') }}"><i class="la la-money-bill" aria-hidden="true"></i> Payroll</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xxl-3">
            <a class="card h-100 text-body text-decoration-none" href="{{ route('expenses.index', ['status' => 'paid', 'paid_month' => now()->format('Y-m')]) }}">
                <div class="card-body">
                    <div class="text-muted mb-2">Paid expenses this month</div>
                    <div class="h3 mb-1">NGN {{ number_format($paidExpenseKobo / 100, 2) }}</div>
                    <small class="text-muted">By payment date</small>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xxl-3">
            <a class="card h-100 text-body text-decoration-none" href="{{ route('expenses.index', ['status' => 'approved']) }}">
                <div class="card-body">
                    <div class="text-muted mb-2">Approved, awaiting payment</div>
                    <div class="h3 mb-1">NGN {{ number_format($approvedExpenseKobo / 100, 2) }}</div>
                    <small class="text-muted">Unpaid approved expenses</small>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xxl-3">
            <a class="card h-100 text-body text-decoration-none" href="{{ route('expenses.index', ['status' => 'submitted']) }}">
                <div class="card-body">
                    <div class="text-muted mb-2">Awaiting founder approval</div>
                    <div class="h3 mb-1">{{ number_format($submittedExpenseCount) }}</div>
                    <small class="text-muted">Submitted expenses</small>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xxl-3">
            <a class="card h-100 text-body text-decoration-none" href="{{ $currentPayroll ? route('payroll.show', $currentPayroll) : route('payroll.index') }}">
                <div class="card-body">
                    <div class="text-muted mb-2">{{ now()->format('F') }} payroll</div>
                    @if ($currentPayroll)
                        <div class="h3 mb-1">{{ ucfirst($currentPayroll->status) }}</div>
                        <small class="text-muted">Net total: NGN {{ number_format(($currentPayroll->entries_sum_net_kobo ?? 0) / 100, 2) }}</small>
                    @else
                        <div class="h3 mb-1">Not created</div>
                        <small class="text-muted">Open payroll to get started</small>
                    @endif
                </div>
            </a>
        </div>
    </div>

    <section aria-labelledby="expense-follow-up-heading">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h4 mb-1" id="expense-follow-up-heading">Expense follow-up</h2>
                <p class="text-muted mb-0">{{ $draftExpenseCount }} drafts · {{ $returnedExpenseCount }} returned</p>
            </div>
            <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary">All expenses</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Expense</th><th>Status</th><th class="text-end">Amount (NGN)</th><th></th></tr></thead>
                <tbody>
                    @forelse ($attentionExpenses as $expense)
                        <tr>
                            <td>{{ $expense->title }}</td>
                            <td>{{ ucfirst($expense->status) }}</td>
                            <td class="text-end">{{ number_format($expense->amount_kobo / 100, 2) }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('expenses.show', $expense) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No drafts or returned expenses need follow-up.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
