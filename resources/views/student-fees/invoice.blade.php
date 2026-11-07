<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px 42px; }
        body { color: #17352e; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        .letterhead { width: 100%; border-bottom: 3px solid #245f4d; padding-bottom: 14px; margin-bottom: 22px; }
        .letterhead td { vertical-align: middle; }
        .logo { max-width: 74px; max-height: 74px; }
        .academy-name { color: #174d3e; font-size: 21px; font-weight: bold; }
        .contact { color: #52635d; font-size: 9px; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        .details, .fees { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details th, .details td { text-align: left; padding: 5px 0; vertical-align: top; }
        .details th { color: #52635d; width: 140px; }
        .fees th { background: #245f4d; color: #fff; text-align: left; padding: 8px; }
        .fees td { border-bottom: 1px solid #d8dfda; padding: 8px; }
        .amount { text-align: right !important; white-space: nowrap; }
        .total { font-weight: bold; }
        .payment-details { margin-top: 20px; page-break-inside: avoid; }
        .payment-details h2 { font-size: 13px; margin: 0 0 8px; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #d8dfda; color: #52635d; font-size: 9px; }
    </style>
</head>
<body>
    <table class="letterhead">
        <tr>
            @if ($academy['logo'])<td style="width: 92px"><img class="logo" src="{{ $academy['logo'] }}" alt="Academy logo"></td>@endif
            <td>
                <div class="academy-name">{{ $academy['name'] }}</div>
                @if ($academy['address'])<div class="contact">{!! nl2br(e($academy['address'])) !!}</div>@endif
                @if ($academy['phone'])<div class="contact">Phone: {{ $academy['phone'] }}</div>@endif
                @if ($academy['email'])<div class="contact">Email: {{ $academy['email'] }}</div>@endif
                @if ($academy['website'])<div class="contact">Website: {{ $academy['website'] }}</div>@endif
            </td>
        </tr>
    </table>

    <h1>Fee Invoice</h1>
    <table class="details">
        <tr><th>Reference</th><td>{{ $reference }}</td></tr>
        <tr><th>Student</th><td>{{ $student->full_name }}</td></tr>
        @if ($student->student_id)<tr><th>Student ID</th><td>{{ $student->student_id }}</td></tr>@endif
        <tr><th>Billing month</th><td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $month.'-01')->format('F Y') }}</td></tr>
        <tr><th>Prepared</th><td>{{ $generatedAt->format('d M Y H:i') }}</td></tr>
    </table>

    <table class="fees">
        <thead><tr><th>Fee</th><th class="amount">Due (NGN)</th><th class="amount">Paid (NGN)</th><th class="amount">Balance (NGN)</th></tr></thead>
        <tbody>
            @foreach ($charges as $charge)
                <tr><td>{{ $charge->fee_name }}</td><td class="amount">{{ number_format($charge->amount_due_kobo / 100, 2) }}</td><td class="amount">{{ number_format($charge->paid_amount_kobo / 100, 2) }}</td><td class="amount">{{ number_format($charge->balance_kobo / 100, 2) }}</td></tr>
            @endforeach
            <tr class="total"><td>Total</td><td class="amount">{{ number_format($charges->sum('amount_due_kobo') / 100, 2) }}</td><td class="amount">{{ number_format($charges->sum(fn ($charge) => $charge->paid_amount_kobo) / 100, 2) }}</td><td class="amount">{{ number_format($charges->sum(fn ($charge) => $charge->balance_kobo) / 100, 2) }}</td></tr>
        </tbody>
    </table>
    @if ($walletBalance > 0)
        <p>Wallet credit available: NGN {{ number_format($walletBalance / 100, 2) }} (not automatically deducted from the balance above).</p>
    @endif
    <div class="payment-details">
        <h2>Payment Details</h2>
        @if ($paymentDetails)
            <table class="details">
                <tr><th>Bank</th><td>{{ $paymentDetails['bank_name'] }}</td></tr>
                <tr><th>Account name</th><td>{{ $paymentDetails['account_name'] }}</td></tr>
                <tr><th>Account number</th><td>{{ $paymentDetails['account_number'] }}</td></tr>
                <tr><th>Payment reference</th><td>{{ $reference }}</td></tr>
            </table>
        @else
            <p>Please contact the academy for payment details.</p>
        @endif
    </div>
    <div class="footer">This invoice reflects recorded fees and payments for the billing month as of {{ $generatedAt->format('d M Y H:i') }}.</div>
</body>
</html>
