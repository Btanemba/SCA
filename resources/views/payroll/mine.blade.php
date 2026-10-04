@extends('payroll.layout')

@section('payroll_content')
    <h2 class="mb-4">My Payslips</h2>
    <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Period</th><th>Payment Status</th><th class="text-end">Net Pay (NGN)</th><th></th></tr></thead><tbody>
        @forelse ($entries as $entry)<tr><td>{{ \Carbon\Carbon::create($entry->payroll->year, $entry->payroll->month, 1)->format('F Y') }}</td><td>{{ $entry->payroll->status === 'paid' ? 'Paid' : 'Approved - payment pending' }}</td><td class="text-end">{{ number_format($entry->net_kobo / 100, 2) }}</td><td><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('payroll.payslip', [$entry, 'inline' => 1]) }}" target="_blank" rel="noopener" title="View payslip" aria-label="View payslip"><i class="la la-eye" aria-hidden="true"></i></a><a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.payslip', $entry) }}" title="Download payslip" aria-label="Download payslip"><i class="la la-download" aria-hidden="true"></i></a></div></td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted">No approved payslips yet.</td></tr>@endforelse
    </tbody></table></div>
    {{ $entries->links() }}
@endsection
