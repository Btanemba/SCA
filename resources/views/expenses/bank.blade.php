@extends('payroll.documents.layout', ['payroll' => $expense])

@section('document_content')
    <table class="meta"><tr><td>Date: {{ $expense->approved_at->format('d F Y') }}</td><td class="right">{{ $expense->reference }}</td></tr></table>
    <p>The Branch Manager<br>{{ $academy['bank_name'] }}<br>{!! nl2br(e($academy['bank_address'] ?? '')) !!}</p>
    <p>Dear Sir/Madam,</p>
    <h1>Expense Bank Payment Instruction</h1>
    <p>Please debit our school account and credit the beneficiary below for the approved purchase.</p>
    <table class="meta">
        <tr><td>School account name</td><td>{{ $academy['debit_account_name'] }}</td></tr>
        <tr><td>School account number</td><td>{{ $academy['debit_account_number'] }}</td></tr>
        <tr><td>Supplier / Payee</td><td>{{ $expense->payee }}</td></tr>
        <tr><td>Beneficiary bank</td><td>{{ $expense->bank_name }}</td></tr>
        <tr><td>Beneficiary account name</td><td>{{ $expense->account_name }}</td></tr>
        <tr><td>Beneficiary account number</td><td>{{ $expense->account_number }}</td></tr>
        <tr><td><strong>Approved amount (NGN)</strong></td><td><strong>{{ number_format($expense->amount_kobo / 100, 2) }}</strong></td></tr>
    </table>
    <h2>{{ $expense->title }}</h2>
    <p>Category: {{ $expense->category }}<br>Expense date: {{ $expense->expense_date->format('d F Y') }}</p>
    <p>{!! nl2br(e($expense->description)) !!}</p>
    <p>Please confirm completion using our contact details above, quoting reference {{ $expense->reference }}.</p>
    <p>Yours faithfully,</p>
    @include('payroll.documents.approval', ['payroll' => $expense])
@endsection
