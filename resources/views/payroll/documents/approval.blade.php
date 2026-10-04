<div class="approval">
    <div class="stamp">APPROVED</div>
    <div>Chief Executive Officer</div>
    <div><img class="signature" src="{{ $academy['signature'] }}" alt="CEO signature"></div>
    <div><strong>{{ $academy['ceo_name'] }}</strong></div>
    <div>Approved: {{ $payroll->approved_at->format('d F Y H:i') }}</div>
    <div>Reference: {{ $payroll->reference }}</div>
</div>
