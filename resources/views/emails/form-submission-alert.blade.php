<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#99f6e4;">{{ $formLabel }}</p>
                            <h1 style="margin:0;font-size:26px;line-height:1.2;font-weight:700;">New form submission</h1>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#d1fae5;">
                                A customer submitted the {{ strtolower($formLabel) }} form. Review the details below or open the record in admin.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d7f3ef;border-radius:12px;overflow:hidden;">
                                @foreach ($details as $label => $value)
                                    <tr>
                                        <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;width:38%;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;vertical-align:top;">{!! nl2br(e((string) $value)) !!}</td>
                                    </tr>
                                @endforeach
                            </table>

                            @if ($mediaPreviewItems !== [])
                                <div style="margin-top:20px;padding:16px;border:1px solid #d7f3ef;border-radius:12px;background-color:#f8fffe;">
                                    <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#0f766e;">Warranty Media</p>

                                    @foreach ($mediaPreviewItems as $item)
                                        <div style="margin:0 0 14px;padding:12px;border:1px solid #cceee9;border-radius:10px;background-color:#ffffff;">
                                            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#073b4c;">{{ $item['name'] }}</p>

                                            @if ($item['is_image'])
                                                <img src="{{ $item['url'] }}" alt="Warranty image" style="display:block;max-width:100%;height:auto;border-radius:8px;border:1px solid #d7f3ef;">
                                            @endif

                                            <p style="margin:10px 0 0;font-size:13px;line-height:1.5;color:#475569;">
                                                @if ($item['is_video'])
                                                    Video submitted. Use the link below to open it.
                                                @else
                                                    If the image preview is blocked by your email client, use the link below.
                                                @endif
                                            </p>

                                            <p style="margin:8px 0 0;">
                                                <a href="{{ $item['url'] }}" style="display:inline-block;background-color:#e6fffa;color:#0f766e;text-decoration:none;font-weight:700;font-size:12px;padding:8px 12px;border-radius:999px;border:1px solid #99f6e4;">
                                                    Open Media
                                                </a>
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <p style="margin:28px 0 0;">
                                <a href="{{ $adminUrl }}" style="display:inline-block;background-color:#14b8a6;color:#041f1e;text-decoration:none;font-weight:700;font-size:14px;padding:12px 18px;border-radius:999px;">
                                    Open in admin
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#f8fffe;border-top:1px solid #d7f3ef;font-size:12px;line-height:1.6;color:#64748b;">
                            H2Systems · Molecular Hydrogen Water · Form notification from Email Mappings
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
