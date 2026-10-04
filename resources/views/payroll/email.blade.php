<p>Dear {{ $payroll->approval_snapshot['bank_name'] }} team,</p>
<p>Please find attached the approved salary payment instruction and beneficiary schedule for {{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}.</p>
<p>Reference: {{ $payroll->reference }}<br>Total payment: NGN {{ number_format($payroll->entries->sum('net_kobo') / 100, 2) }}</p>
<p>Please confirm completion quoting the reference above.</p>
<p>{{ $payroll->approval_snapshot['name'] }}<br>{{ $payroll->approval_snapshot['phone'] }}</p>
