<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warranty Registration Confirmation</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#99f6e4;">H2Systems Warranty</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;font-weight:700;">Registration Confirmed</h1>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#d1fae5;">
                                Thank you, {{ $registration->customer_name }}. Your machine warranty registration has been received.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                                Please keep this email for your records. The details below confirm your warranty registration.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d7f3ef;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Purchaser Name</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;">{{ $registration->customer_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Serial Number</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;font-family:Consolas,Monaco,monospace;color:#073b4c;">{{ $registration->serial_number }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Date Of Purchase</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;">{{ $registration->purchase_date?->format('F j, Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;">Purchased From</td>
                                    <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;">{{ $registration->purchased_from ?: 'Not provided' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;background-color:#f8fffe;font-size:13px;font-weight:700;color:#0f766e;">Date Of Registration</td>
                                    <td style="padding:14px 18px;font-size:14px;color:#073b4c;">{{ $registration->created_at?->timezone(config('app.timezone'))->format('F j, Y g:i A T') }}</td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#64748b;">
                                If any of the information above is incorrect, please contact us so we can update your warranty record.
                            </p>
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
