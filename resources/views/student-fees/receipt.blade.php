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
        .number { color: #52635d; margin-bottom: 20px; }
        .details, .allocations { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details th, .details td { text-align: left; padding: 5px 0; vertical-align: top; }
        .details th { color: #52635d; width: 140px; }
        .allocations th { background: #245f4d; color: #fff; text-align: left; padding: 8px; }
        .allocations td { border-bottom: 1px solid #d8dfda; padding: 8px; }
        .amount { text-align: right !important; white-space: nowrap; }
        .total { font-weight: bold; font-size: 13px; }
        .verification { margin-top: 26px; padding-top: 12px; border-top: 1px solid #d8dfda; font-size: 9px; }
        .url { overflow-wrap: break-word; word-wrap: break-word; }
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

    <h1>Payment Receipt</h1>
    <div class="number">Receipt number: <strong>{{ $receipt->receipt_number }}</strong></div>
    <table class="details">
        <tr><th>Student</th><td>{{ $receipt->student->full_name }}</td></tr>
        @if ($receipt->student->student_id)<tr><th>Student ID</th><td>{{ $receipt->student->student_id }}</td></tr>@endif
        <tr><th>Payment date</th><td>{{ $receipt->paid_at->format('d M Y') }}</td></tr>
        <tr><th>Payment method</th><td>{{ ucwords(str_replace('_', ' ', $receipt->payment_method)) }}</td></tr>
        @if ($receipt->reference)<tr><th>Reference</th><td>{{ $receipt->reference }}</td></tr>@endif
        <tr><th>Received by</th><td>{{ $receipt->receiver->name }}</td></tr>
    </table>

    <table class="allocations">
        <thead><tr><th>Allocation</th><th>Billing month</th><th class="amount">Amount (NGN)</th></tr></thead>
        <tbody>
            @foreach ($receipt->payments as $payment)
                <tr><td>{{ $payment->charge->fee_name }}</td><td>{{ $payment->charge->billing_month->format('F Y') }}</td><td class="amount">{{ number_format($payment->amount_kobo / 100, 2) }}</td></tr>
            @endforeach
            @if ($receipt->walletCredit)
                <tr><td>Student wallet credit</td><td>-</td><td class="amount">{{ number_format($receipt->walletCredit->amount_kobo / 100, 2) }}</td></tr>
            @endif
            <tr class="total"><td colspan="2">Total received</td><td class="amount">{{ number_format($receipt->amount_kobo / 100, 2) }}</td></tr>
        </tbody>
    </table>

    <div class="verification">
        Verify this receipt and compare its details at:<br>
        <a class="url" href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
    </div>
</body>
</html>
