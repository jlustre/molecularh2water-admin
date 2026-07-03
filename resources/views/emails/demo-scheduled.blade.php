<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Scheduled</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border:1px solid #e9d5ff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#3b0764 0%,#5b21b6 100%);color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#e9d5ff;">Demo Scheduled</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;font-weight:700;">You're invited to a {{ $demonstration->type->label() }}</h1>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#ede9fe;">
                                {{ $host->name }} has scheduled a demonstration for you with {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                                Hi {{ $lead->first_name }},
                            </p>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                                Your {{ strtolower($demonstration->type->label()) }} is scheduled for
                                <strong>{{ $demonstration->scheduled_at?->format('l, F j, Y \a\t g:i A') }}</strong>.
                            </p>

                            @if ($demonstration->venue && ! $demonstration->type->isOnline())
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                                    <strong>Location:</strong> {{ $demonstration->venue }}
                                </p>
                            @endif

                            @if ($onlineDemoLink)
                                <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#6d28d9;">Online meeting link</p>
                                <a href="{{ $onlineDemoLink }}" style="display:inline-block;padding:14px 24px;border-radius:999px;background-color:#7c3aed;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">
                                    Join online demo
                                </a>
                                <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                    Or copy this link into your browser:<br>
                                    <a href="{{ $onlineDemoLink }}" style="color:#7c3aed;word-break:break-all;">{{ $onlineDemoLink }}</a>
                                </p>
                            @endif

                            @if ($demonstration->notes)
                                <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#475569;">
                                    <strong>Notes:</strong> {{ $demonstration->notes }}
                                </p>
                            @endif

                            <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                If you need to reschedule, reply to this email or contact {{ $host->name }} directly.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
