<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payroll->reference }}</title>
    <style>
        @page { margin: 35px 38px 45px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #202820; line-height: 1.5; }
        .letterhead { width: 100%; border-bottom: 3px solid #26763d; padding-bottom: 12px; margin-bottom: 22px; }
        .letterhead td { border: 0; padding: 0; vertical-align: middle; }
        .logo-cell { width: 100px; }
        .logo { max-width: 85px; max-height: 85px; }
        .academy-name { font-size: 21px; font-weight: bold; color: #26763d; margin: 0 0 5px; overflow-wrap: break-word; }
        .contact { font-size: 9px; }
        h1 { font-size: 16px; margin: 0 0 12px; }
        h2 { font-size: 13px; }
        .meta { width: 100%; margin-bottom: 15px; }
        .meta td { padding: 3px 0; border: 0; }
        .schedule { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8px; }
        .schedule th, .schedule td { padding: 6px 4px; border: 1px solid #ccd6cc; overflow-wrap: break-word; }
        .schedule th { background: #edf4ee; text-align: left; }
        .schedule thead { display: table-header-group; }
        .schedule tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .approval { margin-top: 24px; page-break-inside: avoid; }
        .stamp { display: inline-block; border: 4px double #21812f; color: #21812f; font-size: 22px; font-weight: bold; padding: 3px 14px; transform: rotate(-8deg); }
        .signature { display: block; max-width: 170px; max-height: 65px; margin: 16px 0 4px; }
        .page-break { page-break-before: always; }
        .footer { position: fixed; bottom: -25px; left: 0; right: 0; font-size: 8px; color: #5a665a; border-top: 1px solid #ccd6cc; padding-top: 5px; }
        .total { font-weight: bold; background: #edf4ee; }
    </style>
</head>
<body>
    <div class="footer">{{ $academy['name'] }} &middot; {{ $payroll->reference }} &middot; Confidential</div>
    @include('payroll.documents.header')
    @yield('document_content')
</body>
</html>
