<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Registration Form</title>
    <style>
        @page { margin: 28px 38px 35px; }
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 10px; line-height: 1.35; }
        .sheet-new-page { page-break-before: always; }
        .letterhead { width: 100%; border-bottom: 2px solid #245f4d; padding-bottom: 8px; margin-bottom: 10px; }
        .letterhead td { vertical-align: middle; }
        .logo { width: 58px; max-height: 65px; }
        .academy-name { font-size: 19px; color: #245f4d; font-weight: bold; }
        .contact { font-size: 9px; }
        h1 { font-size: 17px; margin: 0 0 6px; }
        h2 { font-size: 13px; margin: 12px 0 8px; }
        p { margin: 5px 0; }
        .photo { width: 95px; height: 95px; border: 1px dashed #666; text-align: center; font-size: 9px; vertical-align: middle; }
        .intro { width: 100%; margin-bottom: 7px; }
        .intro td { vertical-align: top; }
        .fields { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .fields td { padding: 5px 8px 3px 0; vertical-align: top; }
        .write-line { height: 25px; border-bottom: 1px solid #888; }
        .box { display: inline-block; width: 9px; height: 9px; border: 1px solid #333; margin-right: 5px; vertical-align: middle; }
        .choice { margin: 8px 0; }
        .declaration { border-top: 1px solid #999; margin-top: 14px; padding-top: 6px; }
        .office { border: 1px solid #999; padding: 9px; margin-top: 16px; }
        .office h2 { margin-top: 0; }
        .contact-section { page-break-inside: avoid; margin-bottom: 14px; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8px; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="footer">{{ $academy['name'] }} | Registration Form | Return the completed form to the school.</div>
    @foreach (['parent', 'student', 'pickup'] as $sectionKey)
        <section class="sheet{{ $loop->first ? '' : ' sheet-new-page' }}">
            <table class="letterhead">
                <tr>
                    @if ($academy['logo'])
                        <td style="width: 72px"><img class="logo" src="{{ $academy['logo'] }}" alt="School logo"></td>
                    @endif
                    <td>
                        <div class="academy-name">{{ $academy['name'] }}</div>
                        @if ($academy['address'])<div class="contact">{!! nl2br(e($academy['address'])) !!}</div>@endif
                        <div class="contact">{{ implode(' | ', array_filter([$academy['phone'], $academy['email'], $academy['website']])) }}</div>
                    </td>
                </tr>
            </table>
            <h1>Registration Form</h1>
            <h2>{{ $sections[$sectionKey]['title'] }}</h2>
            @if ($sectionKey === 'parent')
                <p>Complete in BLOCK CAPITALS and return to the school. Fields marked * are required.</p>
                <p>Complete the parent sheet once per family and a separate student and pickup sheet for each child.</p>
            @else
                <p>Parent / guardian name: ___________________________________________________</p>
            @endif
            @if ($sectionKey === 'student')
                <p>Parent / guardian's relationship to child: _____________________________________ *</p>
                <p class="choice"><span class="box"></span> This parent / guardian is a primary contact for this child.</p>
                <p class="choice"><span class="box"></span> Use the parent's address and background details where applicable.</p>
            @endif
            @if ($sectionKey !== 'pickup')
                <table class="intro"><tr>
                    <td><p>Please attach a recent passport photograph. *</p></td>
                    <td class="photo">Attach {{ $sectionKey === 'parent' ? 'parent / guardian' : 'student' }}<br>passport photograph *</td>
                </tr></table>
                <table class="fields">
                    @foreach (array_chunk($sections[$sectionKey]['fields'], 2, true) as $row)
                        <tr>
                            @foreach ($row as $definition)
                                <td>{{ $definition['label'] }}{{ ($definition['required'] ?? false) ? ' *' : '' }}<div class="write-line"></div></td>
                            @endforeach
                            @if (count($row) === 1)<td></td>@endif
                        </tr>
                    @endforeach
                </table>
                @if ($sectionKey === 'parent')
                    <p class="choice"><span class="box"></span> I will also pick up this child. Complete the authorization on the pickup sheet.</p>
                    <div class="declaration">
                        <p>I confirm that the information I have provided is accurate.</p>
                        <p>Parent / guardian signature: __________________________ Date: __________________</p>
                    </div>
                @else
                    <div class="office">
                        <h2>Office Use Only</h2>
                        <p>Student ID: ____________________ Admission date: ____________________</p>
                        <p>Documents checked by: ____________________ Date: ____________________</p>
                    </div>
                @endif
            @else
                <p>Child's full name: __________________________________________________________</p>
                <p>At least one contact is required. Contact 2 is optional; if used, complete all its required details.</p>
                @for ($slot = 1; $slot <= 2; $slot++)
                    <div class="contact-section">
                        <h2>Contact {{ $slot }}{{ $slot === 2 ? ' (optional)' : ' *' }}</h2>
                        <table class="intro"><tr>
                            <td>
                                <p class="choice"><span class="box"></span> Use the parent / guardian details and photograph from the parent sheet.</p>
                                <p class="choice"><span class="box"></span> Can pick up <span class="box" style="margin-left: 18px"></span> Can drop off</p>
                                <p>Relationship to child is required even when using the parent's details.</p>
                            </td>
                            <td class="photo">Attach contact<br>passport photograph *<br>(unless using parent's)</td>
                        </tr></table>
                        <table class="fields">
                            @foreach (array_chunk($sections['pickup']['fields'], 2, true) as $row)
                                <tr>
                                    @foreach ($row as $definition)
                                        <td>{{ $definition['label'] }}{{ ($definition['required'] ?? false) ? ' *' : '' }}<div class="write-line"></div></td>
                                    @endforeach
                                    @if (count($row) === 1)<td></td>@endif
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endfor
                <div class="declaration">
                    <p>I authorize the people identified above to collect and/or drop off my child as indicated. I will notify the school of any changes to this authorization.</p>
                    <p>Parent / guardian signature: __________________________ Date: __________________</p>
                </div>
                <div class="office">
                    <h2>Office Use Only</h2>
                    <p>Contact 1 RFID: ____________________ Contact 2 RFID: ____________________</p>
                    <p>Verified by: ____________________ Date: ____________________</p>
                </div>
            @endif
        </section>
    @endforeach
</body>
</html>
