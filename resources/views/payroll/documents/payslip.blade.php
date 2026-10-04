@extends('payroll.documents.layout')

@section('document_content')
    <h1>Payslip: {{ $period }}</h1>
    <table class="meta">
        <tr><td>Employee</td><td>{{ $entry->employee_name }}</td></tr>
        <tr><td>Payslip reference</td><td>{{ $payroll->reference }}/{{ $entry->id }}</td></tr>
        <tr><td>Bank</td><td>{{ $entry->bank_name }}</td></tr>
        <tr><td>Account number</td><td>{{ $entry->account_number }}</td></tr>
        <tr><td>Payment status</td><td>{{ $payroll->status === 'paid' ? 'Paid' : 'Approved - payment pending' }}</td></tr>
        @if ($payroll->paid_at)<tr><td>Payment date / reference</td><td>{{ $payroll->paid_at->format('d F Y') }} / {{ $payroll->payment_reference }}</td></tr>@endif
    </table>
    <h2>Earnings</h2>
    <table class="schedule"><thead><tr><th>Description</th><th>Amount (NGN)</th></tr></thead><tbody>
        <tr><td>Basic salary</td><td class="right">{{ number_format($entry->basic_kobo / 100, 2) }}</td></tr>
        @foreach ($entry->allowances as $item)<tr><td>{{ $item['label'] }}</td><td class="right">{{ number_format($item['kobo'] / 100, 2) }}</td></tr>@endforeach
        <tr class="total"><td>Gross pay</td><td class="right">{{ number_format($entry->gross_kobo / 100, 2) }}</td></tr>
    </tbody></table>
    <h2>Deductions</h2>
    <table class="schedule"><thead><tr><th>Description</th><th>Amount (NGN)</th></tr></thead><tbody>
        @forelse ($entry->deductions as $item)<tr><td>{{ $item['label'] }}</td><td class="right">{{ number_format($item['kobo'] / 100, 2) }}</td></tr>@empty<tr><td>No deductions</td><td class="right">0.00</td></tr>@endforelse
        <tr class="total"><td>Total deductions</td><td class="right">{{ number_format($entry->deductions_kobo / 100, 2) }}</td></tr>
        <tr class="total"><td>Net pay</td><td class="right">{{ number_format($entry->net_kobo / 100, 2) }}</td></tr>
    </tbody></table>
    @include('payroll.documents.approval')
@endsection
