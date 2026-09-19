<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation completed · {{ $questionnaire->full_name }}</title>
    @include('partials.favicon')
    <style>
        body { margin: 0; background: #f4fbfb; color: #073b4c; font-family: Arial, Helvetica, sans-serif; }
        .wrap { max-width: 560px; margin: 48px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #bbf7d0; border-radius: 14px; padding: 28px; text-align: center; }
        h1 { margin: 0 0 12px; color: #047857; font-size: 26px; }
        p { color: #334155; line-height: 1.6; }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <h1>Installation completed</h1>
            <p>Thank you. {{ $installation->customer_signature_name }} signed the completion waiver for {{ $questionnaire->full_name }}.</p>
            <p>Completed by {{ $installation->completion_installer_name }} on {{ $installation->installation_date?->format('F j, Y') }}.</p>
            <p>The completion has been recorded for the installation team.</p>
        </section>
    </main>
</body>
</html>
