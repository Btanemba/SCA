<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Receipt Verification | {{ $academyName }}</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; color: #263b34; background: #f3f6f4; line-height: 1.5; }
        header { padding: 20px 24px; background: #245f4d; color: white; font-weight: 600; }
        main { max-width: 700px; margin: 32px auto; padding: 0 24px 32px; }
        h1 { margin-bottom: 4px; font-size: 24px; }
        .status { color: #16623f; font-weight: 600; }
        dl { display: grid; grid-template-columns: minmax(110px, 160px) minmax(0, 1fr); gap: 12px; margin: 28px 0; }
        dt { color: #52635d; }
        dd { margin: 0; overflow-wrap: anywhere; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 0; border-bottom: 1px solid #d8dfda; text-align: left; vertical-align: top; }
        .amount { text-align: right; white-space: nowrap; }
        @media (max-width: 480px) { dl { grid-template-columns: 1fr; gap: 3px; } dd { margin-bottom: 12px; } }
    </style>
</head>
<body>
    <header>{{ $academyName }}</header>
    <main>
        <h1>Receipt verification</h1>
        <div class="status">Verified payment record</div>
        <dl>
            <dt>Receipt number</dt><dd>{{ $receipt->receipt_number }}</dd>
            <dt>Student</dt><dd>{{ $receipt->student->full_name }}@if ($receipt->student->student_id) ({{ $receipt->student->student_id }})@endif</dd>
            <dt>Payment date</dt><dd>{{ $receipt->paid_at->format('d M Y') }}</dd>
            <dt>Payment method</dt><dd>{{ ucwords(str_replace('_', ' ', $receipt->payment_method)) }}</dd>
            <dt>Total received</dt><dd>NGN {{ number_format($receipt->amount_kobo / 100, 2) }}</dd>
        </dl>
        <table>
            <thead><tr><th>Allocation</th><th class="amount">Amount (NGN)</th></tr></thead>
            <tbody>
                @foreach ($receipt->payments as $payment)
                    <tr><td>{{ $payment->charge->fee_name }} ({{ $payment->charge->billing_month->format('F Y') }})</td><td class="amount">{{ number_format($payment->amount_kobo / 100, 2) }}</td></tr>
                @endforeach
                @if ($receipt->walletCredit)
                    <tr><td>Student wallet credit</td><td class="amount">{{ number_format($receipt->walletCredit->amount_kobo / 100, 2) }}</td></tr>
                @endif
            </tbody>
        </table>
    </main>
</body>
</html>
