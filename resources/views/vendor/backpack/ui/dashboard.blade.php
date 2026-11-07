@extends(backpack_view('blank'))

@php
    $currentRoleCode = backpack_user()?->person?->sacRole?->code;
    $isSecurityUser = $currentRoleCode === \App\Models\Person::ROLE_SECURITY;
    $isAccountantUser = $currentRoleCode === \App\Models\Person::ROLE_ACCOUNTANT;
    $isParentUser = $currentRoleCode === \App\Models\Person::ROLE_PARENT;
    $canViewPeopleOverview = in_array($currentRoleCode, \App\Models\Person::ROLES_MANAGE_ACCOUNTS, true);

    if ($isSecurityUser || $isAccountantUser || $isParentUser || $canViewPeopleOverview) {
        $widgets['before_content'] = [];
    } elseif (backpack_theme_config('show_getting_started')) {
        $widgets['before_content'][] = [
            'type'        => 'view',
            'view'        => backpack_view('inc.getting_started'),
        ];
    }
@endphp

@section('title', $isAccountantUser ? 'Finance overview' : trans('backpack::base.dashboard'))

@if ($canViewPeopleOverview)
    @push('after_styles')
        <style>
            .founder-summary { --summary-ink: #20372d; }
            .founder-summary .people-placard {
                min-height: 192px;
                border: 1px solid #dfe7e2;
                border-top: 3px solid var(--placard-accent);
                border-radius: 8px;
                background: #fff;
                color: var(--summary-ink);
                box-shadow: 0 3px 12px rgba(32, 55, 45, .04);
                transition: box-shadow .18s ease, border-color .18s ease;
            }
            .founder-summary .people-placard:hover,
            .founder-summary .people-placard:focus-visible {
                border-color: var(--placard-accent);
                box-shadow: 0 5px 18px rgba(32, 55, 45, .12);
            }
            .founder-summary .people-placard:focus-visible { outline: 2px solid var(--placard-accent); outline-offset: 3px; }
            .founder-summary .placard-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                flex-shrink: 0;
                border-radius: 8px;
                background: var(--placard-tint);
                color: var(--placard-accent);
                font-size: 26px;
            }
            .founder-summary .placard-count { font-size: 40px; font-weight: 700; line-height: 1.2; overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }
            .founder-summary .placard-footer { color: var(--placard-accent); font-size: 13px; }
            .founder-summary .placard-label { font-weight: 600; overflow-wrap: anywhere; }
        </style>
    @endpush
@endif

@section('content')
@if ($isParentUser)
    @include('parent.dashboard')
@elseif ($isSecurityUser)
    @include('security.attendance_content')
@elseif ($canViewPeopleOverview)
    @php
        $peopleCards = [
            ['code' => \App\Models\Person::ROLE_STUDENT, 'label' => 'Students', 'icon' => 'la-graduation-cap', 'accent' => '#287849', 'tint' => '#eaf5ee'],
            ['code' => \App\Models\Person::ROLE_PARENT, 'label' => 'Parents', 'icon' => 'la-user-friends', 'accent' => '#2563a6', 'tint' => '#edf4fc'],
            ['code' => \App\Models\Person::ROLE_STAFF, 'label' => 'Staff', 'icon' => 'la-chalkboard-teacher', 'accent' => '#087f83', 'tint' => '#e8f6f5'],
            ['code' => \App\Models\Person::ROLE_ADMIN, 'label' => 'Administrators', 'icon' => 'la-user-shield', 'accent' => '#b04060', 'tint' => '#fbeef2'],
        ];
    @endphp
    <section class="founder-summary" aria-labelledby="founder-overview-heading">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h2 mb-1" id="founder-overview-heading">Academy overview</h1>
                <p class="text-muted mb-0">School community</p>
            </div>
            <span class="text-muted small">{{ now()->format('l, j F Y') }}</span>
        </div>
        <div class="row g-3">
            @foreach ($peopleCards as $card)
                <div class="col-12 col-sm-6 col-xl-3">
                    <a class="card people-placard h-100 text-decoration-none" href="{{ backpack_url('person?role='.$card['code']) }}" style="--placard-accent: {{ $card['accent'] }}; --placard-tint: {{ $card['tint'] }};" aria-label="{{ $card['label'] }}: {{ number_format($peopleTotals->get($card['code'], 0)) }}">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <span class="placard-label">{{ $card['label'] }}</span>
                                <span class="placard-icon"><i class="la {{ $card['icon'] }}" aria-hidden="true"></i></span>
                            </div>
                            <div class="placard-count mb-3">{{ number_format($peopleTotals->get($card['code'], 0)) }}</div>
                            <div class="placard-footer d-flex align-items-center justify-content-between gap-2 mt-auto">
                                <span>View {{ strtolower($card['label']) }}</span>
                                <i class="la la-arrow-right" aria-hidden="true"></i>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        @if (in_array($currentRoleCode, [\App\Models\Person::ROLE_FOUNDER, \App\Models\Person::ROLE_ADMIN], true))
            @php
                $financeCards = [];
                if ($currentRoleCode === \App\Models\Person::ROLE_FOUNDER) {
                    $financeCards[] = ['label' => 'Paid expenses', 'amount' => $previousMonthExpenseKobo, 'icon' => 'la-file-invoice-dollar', 'accent' => '#b04060', 'tint' => '#fbeef2', 'link' => route('expenses.index', ['status' => 'paid', 'paid_month' => $previousMonth->format('Y-m')]), 'footer' => 'View expenses'];
                    $financeCards[] = ['label' => 'Total net payroll', 'amount' => $previousMonthPayrollKobo, 'icon' => 'la-money-bill', 'accent' => '#2563a6', 'tint' => '#edf4fc', 'link' => route('payroll.index', ['month' => $previousMonth->month, 'year' => $previousMonth->year]), 'footer' => 'View payroll'];
                }
            @endphp
            @if ($financeCards !== [])
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 mb-3">
                    <h2 class="h4 mb-0" id="previous-month-finance-heading">Previous month finances</h2>
                    <span class="text-muted">{{ $previousMonth->format('F Y') }}</span>
                </div>
                <div class="row g-3" aria-labelledby="previous-month-finance-heading">
                    @foreach ($financeCards as $card)
                        <div class="col-12 col-md-6">
                            <a class="card people-placard h-100 text-decoration-none" href="{{ $card['link'] }}" style="--placard-accent: {{ $card['accent'] }}; --placard-tint: {{ $card['tint'] }};">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                        <div>
                                            <div class="placard-label">{{ $card['label'] }}</div>
                                            <div class="text-muted small">{{ $previousMonth->format('F Y') }}</div>
                                        </div>
                                        <span class="placard-icon"><i class="la {{ $card['icon'] }}" aria-hidden="true"></i></span>
                                    </div>
                                    <div class="placard-count mb-3"><span class="fs-5">NGN</span> {{ number_format($card['amount'] / 100, 2) }}</div>
                                    <div class="placard-footer d-flex align-items-center justify-content-between gap-2 mt-auto">
                                        <span>{{ $card['footer'] }}</span>
                                        <i class="la la-arrow-right" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 mb-3">
                <h2 class="h4 mb-0" id="current-month-finance-heading">Current month finances</h2>
                <span class="text-muted">{{ now()->format('F Y') }}</span>
            </div>
            <div class="row g-3" aria-labelledby="current-month-finance-heading">
                <div class="col-12 col-md-6">
                    <a class="card people-placard h-100 text-decoration-none" href="{{ route('student-fees.index', ['month' => now()->format('Y-m')]) }}" style="--placard-accent: #287849; --placard-tint: #eaf5ee;">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <div class="placard-label">Fee payments received this month</div>
                                    <div class="text-muted small">By payment date</div>
                                </div>
                                <span class="placard-icon"><i class="la la-coins" aria-hidden="true"></i></span>
                            </div>
                            <div class="placard-count mb-3"><span class="fs-5">NGN</span> {{ number_format($currentMonthFeeIncomeKobo / 100, 2) }}</div>
                            <div class="placard-footer d-flex align-items-center justify-content-between gap-2 mt-auto">
                                <span>View child fees</span>
                                <i class="la la-arrow-right" aria-hidden="true"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @endif
    </section>
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
            <a class="card h-100 text-body text-decoration-none" href="{{ route('student-fees.index', ['month' => now()->format('Y-m')]) }}">
                <div class="card-body">
                    <div class="text-muted mb-2">Fee payments received this month</div>
                    <div class="h3 mb-1">NGN {{ number_format($currentMonthFeeIncomeKobo / 100, 2) }}</div>
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
