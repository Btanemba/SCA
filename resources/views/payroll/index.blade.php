@extends('payroll.layout')

@section('payroll_content')
    <h2 class="mb-4">Monthly Payroll</h2>
    @if (backpack_user()->person->sacRole->code === \App\Models\Person::ROLE_ACCOUNTANT)
        <form action="{{ route('payroll.store') }}" method="post" class="row g-3 align-items-end mb-4">
            @csrf
            <div class="col-sm-4 col-md-3"><label for="month" class="form-label">Month</label><select id="month" name="month" class="form-select" required>
                @for ($month = 1; $month <= 12; $month++)<option value="{{ $month }}" @selected((int) old('month', now()->month) === $month)>{{ \Carbon\Carbon::create(2000, $month, 1)->format('F') }}</option>@endfor
            </select></div>
            <div class="col-sm-4 col-md-2"><label for="year" class="form-label">Year</label><input type="number" id="year" name="year" class="form-control" min="2000" max="2100" value="{{ old('year', now()->year) }}" required></div>
            <div class="col-sm-4"><button class="btn btn-primary" type="submit"><i class="la la-plus" aria-hidden="true"></i> Create Payroll</button></div>
        </form>
    @endif
    <form action="{{ route('payroll.index') }}" method="get" role="search" class="row g-2 align-items-end mb-3">
        <div class="col-sm-6 col-md-3">
            <label for="filter-month" class="form-label">Month</label>
            <select id="filter-month" name="month" class="form-select">
                <option value="">All months</option>
                @for ($month = 1; $month <= 12; $month++)<option value="{{ $month }}" @selected((string) request('month') === (string) $month)>{{ \Carbon\Carbon::create(2000, $month, 1)->format('F') }}</option>@endfor
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label for="filter-year" class="form-label">Year</label>
            <input id="filter-year" type="number" name="year" min="2000" max="2100" value="{{ request('year') }}" class="form-control" placeholder="Any year">
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="payroll-search" class="form-label">Reference or status</label>
            <input id="payroll-search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Period, reference, or status">
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit"><i class="la la-search" aria-hidden="true"></i> Search</button></div>
        @if (request()->anyFilled(['month', 'year', 'search']))<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('payroll.index') }}">Clear</a></div>@endif
    </form>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Period</th><th>Reference</th><th>Status</th><th class="text-end">Net Total (NGN)</th><th></th></tr></thead>
            <tbody>@forelse ($payrolls as $payroll)
                <tr><td>{{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}</td><td>{{ $payroll->reference }}</td><td>{{ ucfirst($payroll->status) }}</td><td class="text-end">{{ number_format(($payroll->entries_sum_net_kobo ?? 0) / 100, 2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.show', $payroll) }}"><i class="la la-eye" aria-hidden="true"></i> Open</a></td></tr>
            @empty<tr><td colspan="5" class="text-center text-muted">{{ request()->anyFilled(['month', 'year', 'search']) ? 'No payrolls match your filters.' : 'No payrolls yet.' }}</td></tr>@endforelse</tbody>
        </table>
    </div>
    {{ $payrolls->links() }}
@endsection
