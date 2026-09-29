<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete your registration</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f4ed; font-family:'Segoe UI', Helvetica, Arial, sans-serif; color:#172b25;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Set your password to finish setting up your Springcare Academy account.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f4ed; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border:1px solid #e4e2d8; border-radius:18px; overflow:hidden;">

                    <tr>
                        <td align="center" style="background-color:#235b48; padding:30px 24px;">
                            <div style="color:#d6e756; font-size:11px; font-weight:700; letter-spacing:.22em; text-transform:uppercase; margin-bottom:8px;">Springcare</div>
                            <div style="color:#ffffff; font-size:24px; font-weight:700; letter-spacing:.01em;">Academy</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 34px 8px;">
                            <p style="margin:0 0 6px; color:#235b48; font-size:11px; font-weight:700; letter-spacing:.16em; text-transform:uppercase;">Account invitation</p>
                            <h1 style="margin:0 0 18px; font-size:23px; line-height:1.25; font-weight:700;">Hello {{ $name }},</h1>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6; color:#3c4a44;">
                                An account has been created for you at Springcare Academy.
                            </p>
                            <p style="margin:0 0 26px; font-size:15px; line-height:1.6; color:#3c4a44;">
                                Use the button below to confirm this email address and choose your password. You'll be signed in straight away.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:0 34px 26px;">
                            <a href="{{ $url }}"
                               style="background-color:#235b48; border-radius:12px; color:#ffffff; display:inline-block; font-size:16px; font-weight:700; padding:15px 34px; text-decoration:none;">
                                Complete registration
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 34px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8f3; border:1px solid #e7e9df; border-radius:12px;">
                                <tr>
                                    <td style="padding:14px 18px; font-size:13px; line-height:1.55; color:#52645d;">
                                        This link expires in <strong>{{ $minutes }} minutes</strong> and can only be used once.
                                        Please don't forward it to anyone.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="border-top:1px solid #eeefe7; padding:22px 34px 28px;">
                            <p style="margin:0 0 10px; font-size:12px; line-height:1.55; color:#7b8782;">
                                Button not working? Copy and paste this address into your browser:
                            </p>
                            <p style="margin:0 0 18px; font-size:12px; line-height:1.5; word-break:break-all;">
                                <a href="{{ $url }}" style="color:#235b48;">{{ $url }}</a>
                            </p>
                            <p style="margin:0; font-size:12px; line-height:1.55; color:#7b8782;">
                                If you were not expecting this email, you can safely ignore it.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0; font-size:12px; color:#8a938e;">
                    &copy; {{ date('Y') }} Springcare Academy
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
