<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation packet · {{ $questionnaire->full_name }}</title>
    @include('partials.favicon')
    <style>
        :root { color: #073b4c; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; background: #f4fbfb; }
        .page { max-width: 980px; margin: 0 auto; padding: 28px 16px 48px; }
        .header, .section { background: #fff; border: 1px solid #d7f3ef; border-radius: 12px; }
        .header { padding: 24px; display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; }
        .eyebrow { margin: 0 0 8px; color: #0f766e; font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        h1, h2, p { margin-top: 0; }
        h1 { margin-bottom: 8px; font-size: 30px; }
        h2 { margin-bottom: 18px; font-size: 19px; }
        .muted { color: #64748b; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .button { display: inline-block; border: 1px solid #99f6e4; border-radius: 7px; padding: 10px 14px; color: #0f766e; background: #fff; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button.primary { border-color: #14b8a6; background: #14b8a6; color: #041f1e; }
        .button.success { border-color: #059669; background: #059669; color: #fff; }
        .sections { display: grid; gap: 16px; margin-top: 16px; }
        .section { padding: 22px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 28px; }
        dt { color: #64748b; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
        dd { margin: 0; white-space: pre-line; line-height: 1.5; }
        .photos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .photo { margin: 0; }
        .photo img { display: block; width: 100%; max-height: 360px; object-fit: contain; border: 1px solid #d7f3ef; border-radius: 8px; background: #f8fffe; }
        .photo figcaption { margin-top: 6px; color: #64748b; font-size: 12px; }
        @media (max-width: 640px) { .header, .grid { display: block; } .header .actions { margin-top: 18px; } .grid > div { margin-bottom: 14px; } .photos { grid-template-columns: 1fr; } }
        @media print { body { background: #fff; } .page { max-width: none; padding: 0; } .header, .section { border-color: #cbd5e1; break-inside: avoid; } .actions { display: none; } }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <div>
                <p class="eyebrow">Complete installation packet</p>
                <h1>{{ $questionnaire->full_name }}</h1>
                <p class="muted">Job #{{ $installation->id }} · {{ $installation->scheduled_at?->format('M j, Y g:i A') ?: 'Schedule not set' }}</p>
            </div>
            <div class="actions">
                <button class="button primary" onclick="window.print()" type="button">Print / Save PDF</button>
                @if ($installation->status !== \App\Enums\InstallerInstallationStatus::Completed)
                    <a class="button success" href="{{ $completionUrl }}">Complete installation</a>
                @else
                    <span class="button">Completed {{ $installation->completed_at?->format('M j, Y') }}</span>
                @endif
            </div>
        </header>

        <div class="sections">
            <section class="section">
                <h2>Customer and seller</h2>
                <dl class="grid">
                    <div><dt>Customer</dt><dd>{{ $questionnaire->full_name }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $questionnaire->phone }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $questionnaire->email }}</dd></div>
                    <div><dt>Seller provided by customer</dt><dd>{{ $questionnaire->seller_name ?: 'Not provided' }}</dd></div>
                    <div><dt>Assigned seller</dt><dd>{{ $questionnaire->seller?->name ?: 'Not assigned' }}{{ $questionnaire->seller?->email ? ' · '.$questionnaire->seller->email : '' }}</dd></div>
                    <div><dt>Own or rent</dt><dd>{{ $questionnaire->ownershipLabel() }}</dd></div>
                </dl>
            </section>

            <section class="section">
                <h2>Installation information</h2>
                <dl class="grid">
                    <div><dt>Installation address</dt><dd>{{ $questionnaire->formatted_address }}</dd></div>
                    <div><dt>Property type</dt><dd>{{ $questionnaire->property_type }}</dd></div>
                    <div><dt>Existing equipment</dt><dd>{{ $questionnaire->existingEquipmentLabel() }}</dd></div>
                    <div><dt>Water source</dt><dd>{{ $questionnaire->waterSourceLabel() }}</dd></div>
                    <div><dt>Special requirements</dt><dd>{{ $questionnaire->special_requirements ?: 'None provided' }}</dd></div>
                    <div><dt>Customer additional notes</dt><dd>{{ $questionnaire->additional_notes ?: 'None provided' }}</dd></div>
                    <div><dt>Assignment notes</dt><dd>{{ $questionnaire->assignment_notes ?: 'None provided' }}</dd></div>
                </dl>
            </section>

            <section class="section">
                <h2>Uploaded sink photos</h2>
                @if ($photoUrls !== [])
                    <div class="photos">
                        @foreach ($photoUrls as $index => $photo)
                            <figure class="photo">
                                <img src="{{ $photo['url'] }}" alt="Uploaded sink photo {{ $index + 1 }}">
                                <figcaption>{{ $photo['original_name'] ?: 'Photo '.($index + 1) }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <p class="muted">No sink photos were uploaded.</p>
                @endif
            </section>
        </div>
    </main>
</body>
</html>