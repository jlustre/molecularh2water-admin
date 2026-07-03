<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration Invite</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#99f6e4;">Member Invite</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;font-weight:700;">You're invited to join</h1>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#d1fae5;">
                                {{ $sponsor->name }} has invited you to create your {{ config('app.name') }} member account.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @if ($personalMessage)
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;font-style:italic;">
                                    “{{ $personalMessage }}”
                                </p>
                            @endif

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                                Use the button below to register. This is a one-time invite link.
                            </p>

                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#0f766e;">Registration code</p>
                            <p style="margin:0 0 24px;font-size:18px;font-family:Consolas,Monaco,monospace;font-weight:700;color:#073b4c;">{{ $invite->code }}</p>

                            <a href="{{ $inviteUrl }}" style="display:inline-block;padding:14px 24px;border-radius:999px;background-color:#0d9488;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">
                                Accept invite &amp; register
                            </a>

                            <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                Or copy this link into your browser:<br>
                                <a href="{{ $inviteUrl }}" style="color:#0d9488;word-break:break-all;">{{ $inviteUrl }}</a>
                            </p>

                            @if ($invite->expires_at)
                                <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                    This invite expires on {{ $invite->expires_at->format('F j, Y') }}.
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
