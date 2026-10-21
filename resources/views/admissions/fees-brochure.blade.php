<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px 42px; }
        body { color: #17352e; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        .letterhead { width: 100%; border-bottom: 3px solid #245f4d; padding-bottom: 16px; margin-bottom: 24px; }
        .letterhead td { vertical-align: middle; }
        .logo { width: 74px; max-height: 74px; margin-right: 18px; }
        .academy-name { color: #174d3e; font-size: 21px; font-weight: bold; }
        .contact { color: #52635d; font-size: 9px; }
        h1 { margin: 0 0 4px; color: #17352e; font-size: 22px; }
        .prepared { margin: 0 0 18px; color: #52635d; }
        table.fees { width: 100%; border-collapse: collapse; margin: 16px 0 22px; }
        .fees th { padding: 10px 9px; background: #245f4d; color: #fff; text-align: left; }
        .fees td { padding: 9px; border-bottom: 1px solid #d8dfda; }
        .fees tr:nth-child(even) td { background: #f5f6f2; }
        .amount { font-weight: bold; white-space: nowrap; }
        .payment { padding: 13px 15px; border-left: 4px solid #b65e2b; background: #fff4e5; }
        .payment strong { display: block; margin-bottom: 4px; }
        .footer { margin-top: 26px; padding-top: 9px; border-top: 1px solid #d8dfda; color: #52635d; font-size: 9px; }
    </style>
</head>
<body>
    <table class="letterhead">
        <tr>
            @if ($academy['logo'])
                <td style="width: 92px"><img class="logo" src="{{ $academy['logo'] }}" alt="Academy logo"></td>
            @endif
            <td>
                <div class="academy-name">{{ $academy['name'] }}</div>
                @if ($academy['address'])<div class="contact"><strong>School Address:</strong> {!! nl2br(e($academy['address'])) !!}</div>@endif
                @if ($academy['phone'])<div class="contact"><strong>Phone:</strong> {{ $academy['phone'] }}</div>@endif
                @if ($academy['email'])<div class="contact"><strong>Email Address:</strong> {{ $academy['email'] }}</div>@endif
                @if ($academy['website'])<div class="contact"><strong>Website:</strong> {{ $academy['website'] }}</div>@endif
            </td>
        </tr>
    </table>

    <h1>Fees &amp; Tuition</h1>
    <p class="prepared">Current fee schedule &middot; {{ $preparedAt }}</p>

    <table class="fees">
        <thead>
            <tr>
                <th>Fee</th>
                <th>Age or Level</th>
                <th>Amount</th>
                <th>Schedule</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fees as $fee)
                <tr>
                    <td>{{ $fee->fee_name }}</td>
                    <td>{{ $fee->age_or_level }}</td>
                    <td class="amount">{{ $fee->amount === null ? 'Coming soon' : 'N'.number_format($fee->amount) }}</td>
                    <td>{{ $fee->schedule }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Fee information is currently being updated. Please contact the academy.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="payment">
        <strong>Payment Warning</strong>
        All payments must be made only to the school's official bank account. Cash payments are not accepted.
        Send proof of payment by email or to the school's WhatsApp number, including your child's name and ID number.
    </div>

    <div class="footer">Please contact the academy to confirm any fee details that may change.</div>
</body>
</html>
