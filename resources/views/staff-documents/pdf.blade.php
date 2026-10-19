<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 38px 48px 48px; }
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.65; }
        .letterhead { border-bottom: 2px solid #245848; margin-bottom: 28px; padding-bottom: 14px; }
        .logo { float: left; max-height: 72px; max-width: 110px; margin-right: 18px; }
        .school-name { color: #245848; font-size: 20px; font-weight: bold; margin: 0 0 4px; }
        .contact { color: #555; font-size: 9px; }
        .clear { clear: both; }
        .date { text-align: right; margin: 0 0 24px; }
        h1 { font-size: 16px; text-align: center; text-transform: uppercase; margin: 0 0 28px; }
        .recipient { margin-bottom: 20px; }
        .signatures { margin-top: 44px; }
        .academy-signature { max-height: 58px; max-width: 180px; display: block; margin: 8px 0 2px; }
        .signature-name { font-weight: bold; }
        .staff-signature { margin-top: 42px; page-break-inside: avoid; }
        .line { border-top: 1px solid #333; width: 250px; margin-top: 32px; padding-top: 5px; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; color: #666; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <header class="letterhead">
        @if ($branding['logo']) <img class="logo" src="{{ $branding['logo'] }}" alt=""> @endif
        <p class="school-name">{{ $branding['name'] }}</p>
        <div class="contact">
            @if ($branding['address']) <div>{{ $branding['address'] }}</div> @endif
            @if ($branding['phone'] || $branding['email']) <div>{{ $branding['phone'] }} @if ($branding['phone'] && $branding['email']) | @endif {{ $branding['email'] }}</div> @endif
            @if ($branding['website']) <div>{{ $branding['website'] }}</div> @endif
        </div>
        <div class="clear"></div>
    </header>

    <p class="date">{{ $documentDate->format('F j, Y') }}</p>
    <h1>{{ $document->title }}</h1>
    <div class="recipient">
        <strong>To:</strong> {{ $document->person?->full_name }}
        @if ($document->person?->address_line_1)<br>{{ $document->person->address_line_1 }}@endif
    </div>
    <div>{!! nl2br(e($document->body)) !!}</div>

    <div class="signatures">
        @if ($branding['signature']) <img class="academy-signature" src="{{ $branding['signature'] }}" alt=""> @endif
        @if ($branding['ceo_name']) <div class="signature-name">{{ $branding['ceo_name'] }}</div> @endif
        <div>{{ $branding['name'] }}</div>
    </div>

    @if ($document->requires_staff_signature)
        <div class="staff-signature">
            <p>I acknowledge receipt of this document.</p>
            <div class="line">Staff signature</div>
            <div class="line">Date</div>
        </div>
    @endif

    <footer class="footer">{{ $branding['name'] }} | {{ $document->title }}</footer>
</body>
</html>
