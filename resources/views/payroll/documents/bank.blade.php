@extends('payroll.documents.layout')

@section('document_content')
    <table class="meta"><tr><td>Date: {{ $payroll->approved_at->format('d F Y') }}</td><td class="right">{{ $payroll->reference }}</td></tr></table>
    <p>The Branch Manager<br>{{ $academy['bank_name'] }}<br>{!! nl2br(e($academy['bank_address'] ?? '')) !!}</p>
    <p>Dear Sir/Madam,</p>
    <h1>Salary Payment Instruction: {{ $period }}</h1>
    <p>Please debit our account detailed below and credit the beneficiaries listed in the attached approved payroll schedule for {{ $period }}.</p>
    <table class="meta">
        <tr><td>Account name</td><td>{{ $academy['debit_account_name'] }}</td></tr>
        <tr><td>Account number</td><td>{{ $academy['debit_account_number'] }}</td></tr>
        <tr><td>Number of beneficiaries</td><td>{{ $payroll->entries->count() }}</td></tr>
        <tr><td><strong>Total payment (NGN)</strong></td><td><strong>{{ number_format($payroll->entries->sum('net_kobo') / 100, 2) }}</strong></td></tr>
    </table>
    <p>Please confirm completion using our contact details above, quoting the payroll reference.</p>
    <p>Yours faithfully,</p>
    @include('payroll.documents.approval')
    <div class="page-break"></div>
    @include('payroll.documents.header')
    <h1>Approved Payroll Schedule: {{ $period }}</h1>
    <p>Reference: {{ $payroll->reference }} &middot; Currency: Nigerian naira (NGN)</p>
    <table class="schedule"><thead><tr><th style="width:5%">No.</th><th style="width:20%">Employee</th><th style="width:18%">Bank</th><th style="width:22%">Account Name</th><th style="width:17%">Account Number</th><th style="width:18%">Net Pay (NGN)</th></tr></thead><tbody>
        @foreach ($payroll->entries as $entry)<tr><td>{{ $loop->iteration }}</td><td>{{ $entry->employee_name }}</td><td>{{ $entry->bank_name }}</td><td>{{ $entry->account_name }}</td><td>{{ $entry->account_number }}</td><td class="right">{{ number_format($entry->net_kobo / 100, 2) }}</td></tr>@endforeach
        <tr class="total"><td colspan="5">Total Payment</td><td class="right">{{ number_format($payroll->entries->sum('net_kobo') / 100, 2) }}</td></tr>
    </tbody></table>
    @include('payroll.documents.approval')
@endsection
