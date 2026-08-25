<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation assignment</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    @php
        $seller = $questionnaire->seller;
        $compactRows = array_filter([
            'Customer' => $questionnaire->full_name,
            'Phone' => $questionnaire->phone,
            'Email' => $questionnaire->email,
            'Address' => str_replace("\n", ', ', $questionnaire->formatted_address),
            'Seller' => $seller
                ? trim($seller->name.($seller->email ? ' · '.$seller->email : ''))
                : 'Not set',
            'Scheduled' => $installation->scheduled_at?->timezone(config('app.timezone'))->format('D, M j, Y g:i A') ?: 'Not set',
            'Property' => $questionnaire->property_type,
            'Own / rent' => $questionnaire->ownershipLabel(),
            'Water' => $questionnaire->waterSourceLabel(),
            'Equipment' => $questionnaire->existingEquipmentLabel(),
            'Special req.' => $questionnaire->special_requirements ?: null,
            'Notes' => $questionnaire->additional_notes ?: null,
            'Dispatch notes' => $questionnaire->assignment_notes ?: null,
        ], fn ($value) => filled($value));
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#99f6e4;">Installation assignment</p>
                            <h1 style="margin:0;font-size:22px;line-height:1.25;font-weight:700;">Hi {{ $installer->name }}, a job is ready for you</h1>
                            <p style="margin:10px 0 0;font-size:14px;line-height:1.5;color:#d1fae5;">
                                Review the details, then accept or decline below.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 20px 8px;text-align:center;">
                            <a href="{{ $acceptUrl }}" style="display:inline-block;margin:0 6px 10px;background-color:#14b8a6;color:#041f1e;text-decoration:none;font-weight:700;font-size:14px;padding:11px 18px;border-radius:999px;">
                                Accept assignment
                            </a>
                            <a href="{{ $rejectUrl }}" style="display:inline-block;margin:0 6px 10px;background-color:#ffffff;color:#b45309;text-decoration:none;font-weight:700;font-size:14px;padding:11px 18px;border-radius:999px;border:1px solid #f6ad55;">
                                Decline with reason
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 20px 20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d7f3ef;border-radius:10px;overflow:hidden;">
                                @foreach ($compactRows as $label => $value)
                                    <tr>
                                        <td style="padding:8px 12px;background-color:#f8fffe;border-bottom:1px solid #e7f6f3;font-size:11px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#0f766e;width:28%;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:8px 12px;border-bottom:1px solid #e7f6f3;font-size:13px;line-height:1.45;color:#073b4c;vertical-align:top;">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>

                            @if ($photoPreviews !== [])
                                <p style="margin:16px 0 8px;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#0f766e;">Sink photos</p>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        @foreach ($photoPreviews as $photo)
                                            <td style="padding:0 6px 8px 0;width:50%;vertical-align:top;">
                                                <a href="{{ $photo['url'] }}" style="text-decoration:none;color:#0f766e;">
                                                    <img src="{{ $photo['url'] }}" alt="{{ $photo['name'] }}" style="display:block;width:100%;max-height:140px;object-fit:cover;border-radius:8px;border:1px solid #d7f3ef;">
                                                    <span style="display:block;margin-top:4px;font-size:11px;font-weight:700;">{{ $photo['name'] }}</span>
                                                </a>
                                            </td>
                                            @if ($loop->iteration % 2 === 0 && ! $loop->last)
                                                </tr><tr>
                                            @endif
                                        @endforeach
                                        @if (count($photoPreviews) % 2 === 1)
                                            <td style="width:50%;"></td>
                                        @endif
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 20px;background-color:#f8fffe;border-top:1px solid #d7f3ef;font-size:11px;line-height:1.5;color:#64748b;">
                            These accept/decline links expire in 14 days. If the buttons do not work, reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
