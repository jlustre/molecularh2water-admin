<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer response</title>
</head>
<body style="margin:0;padding:0;background-color:#f4fbfb;font-family:Arial,Helvetica,sans-serif;color:#073b4c;">
    @php
        $accepted = $installation->assignment_response === \App\Enums\InstallerAssignmentResponse::Accepted;
        $badgeBg = $accepted ? '#059669' : '#dc2626';
        $badgeLabel = $accepted ? 'Accepted' : 'Declined';
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4fbfb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background-color:#ffffff;border:1px solid #d7f3ef;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:linear-gradient(135deg,#041f1e 0%,#073b4c 100%);color:#ffffff;">
                            <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#99f6e4;">Installer response</p>
                            <h1 style="margin:0;font-size:22px;line-height:1.25;font-weight:700;">
                                {{ $installer->name }} {{ $accepted ? 'accepted' : 'declined' }} this assignment
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px;font-size:14px;line-height:1.6;color:#334155;">
                            <p style="margin:0 0 14px;">
                                Hi {{ $assignor?->name ?: 'there' }},
                            </p>
                            <p style="margin:0 0 14px;">
                                <span style="display:inline-block;background-color:{{ $badgeBg }};color:#ffffff;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:5px 10px;border-radius:999px;">
                                    {{ $badgeLabel }}
                                </span>
                            </p>
                            <p style="margin:0 0 10px;"><strong>Customer:</strong> {{ $questionnaire->full_name }}</p>
                            <p style="margin:0 0 10px;"><strong>Installer:</strong> {{ $installer->name }}{{ $installer->email ? ' · '.$installer->email : '' }}{{ $installer->phone ? ' · '.$installer->phone : '' }}</p>
                            @if (! $accepted)
                                <p style="margin:0 0 10px;"><strong>Reason:</strong> {{ $installation->assignment_rejection_reason?->label() ?: 'Not provided' }}</p>
                                @if ($installation->assignment_rejection_notes)
                                    <p style="margin:0 0 10px;"><strong>Details:</strong> {{ $installation->assignment_rejection_notes }}</p>
                                @endif
                                <p style="margin:0 0 16px;">This submission is unassigned so you can choose another installer.</p>
                            @else
                                <p style="margin:0 0 16px;">The installer confirmed they can take this installation.</p>
                            @endif
                            <a href="{{ $adminUrl }}" style="display:inline-block;background-color:#14b8a6;color:#041f1e;text-decoration:none;font-weight:700;font-size:14px;padding:11px 18px;border-radius:999px;">
                                Open submission
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
