<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Installation Questionnaire</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#99f6e4;">H2Systems Shipping</p>
                            <h1 style="margin:0;font-size:26px;line-height:1.2;font-weight:700;">New Pre-Installation Questionnaire</h1>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#d1fae5;">
                                Submission #{{ $questionnaire->id }} from {{ $questionnaire->full_name }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d7f3ef;border-radius:12px;overflow:hidden;">
                                @php
                                    $rows = [
                                        'Name' => e($questionnaire->full_name),
                                        'Email' => e($questionnaire->email),
                                        'Phone' => e($questionnaire->phone),
                                        'Installation Address' => nl2br(e($questionnaire->formatted_address)),
                                        'Property Type' => e($questionnaire->property_type),
                                        'Existing Equipment' => e(
                                            $questionnaire->existing_equipment
                                                ? implode(', ', $questionnaire->existing_equipment)
                                                : 'None selected'
                                        ),
                                        'Own or Rent' => e($questionnaire->ownershipLabel()),
                                        'Water Source' => e(
                                            $questionnaire->water_source === 'Other'
                                                ? 'Other: '.($questionnaire->water_source_other ?: 'Not provided')
                                                : $questionnaire->water_source
                                        ),
                                        'Special Requirements' => nl2br(e($questionnaire->special_requirements ?: 'None')),
                                        'Additional Notes' => nl2br(e($questionnaire->additional_notes ?: 'None')),
                                        'Sink Photos' => e(
                                            $questionnaire->hasSinkPhotos()
                                                ? collect($questionnaire->sinkPhotoItems())
                                                    ->map(fn ($photo, $index) => $photo['original_name'] ?: 'Photo '.($index + 1))
                                                    ->implode(', ')
                                                : 'Not provided'
                                        ),
                                        'Submitted' => e($questionnaire->created_at?->timezone(config('app.timezone'))->format('F j, Y g:i A T')),
                                    ];
                                @endphp
                                @foreach ($rows as $label => $value)
                                    <tr>
                                        <td style="padding:14px 18px;background-color:#f8fffe;border-bottom:1px solid #d7f3ef;font-size:13px;font-weight:700;color:#0f766e;width:38%;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:14px 18px;border-bottom:1px solid #d7f3ef;font-size:14px;color:#073b4c;vertical-align:top;">{!! $value !!}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#f8fffe;border-top:1px solid #d7f3ef;font-size:12px;line-height:1.6;color:#64748b;">
                            H2Systems · Molecular Hydrogen Water · Pre-Installation Questionnaire
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
