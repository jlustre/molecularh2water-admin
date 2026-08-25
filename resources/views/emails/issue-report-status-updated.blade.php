<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Report Status Update</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#99f6e4;">H2Systems Support</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;font-weight:700;">Status update for {{ $report->reference_code }}</h1>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#d1fae5;">
                                Hello {{ $report->reporter_name }}, your reported issue is now {{ $report->status->label() }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                                {{ $report->status->reporterMessage() }}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d7f3ef;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Reference</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;font-family:Consolas,Monaco,monospace;color:#073b4c;">{{ $report->reference_code }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Title</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;">{{ $report->title }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Previous status</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;">{{ $statusUpdate->from_status?->label() ?: 'New' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Current status</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;">{{ $report->status->label() }}</td>
                                </tr>
                                @if (filled($report->resolution_summary))
                                    <tr>
                                        <td style="padding:14px 18px;background-color:#f8fffe;font-size:13px;font-weight:700;color:#0f766e;">Resolution notes</td>
                                        <td style="padding:14px 18px;font-size:14px;line-height:1.6;color:#073b4c;">{{ $report->resolution_summary }}</td>
                                    </tr>
                                @endif
                            </table>

                            @if (filled($statusUpdate->note) && $statusUpdate->note !== $report->resolution_summary)
                                <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#334155;">
                                    {{ $statusUpdate->note }}
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#f8fffe;border-top:1px solid #d7f3ef;font-size:12px;line-height:1.6;color:#64748b;">
                            H2Systems · Molecular Hydrogen Water
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
