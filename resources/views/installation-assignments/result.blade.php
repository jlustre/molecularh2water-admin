<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Molecular H2 Water</title>
    @include('partials.favicon')
    <style>
        body { margin: 0; background: #f4fbfb; color: #073b4c; font-family: Arial, Helvetica, sans-serif; }
        .wrap { max-width: 520px; margin: 48px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #d7f3ef; border-radius: 14px; overflow: hidden; }
        .hero { padding: 22px 24px; background: linear-gradient(135deg, #041f1e 0%, #073b4c 100%); color: #fff; }
        .hero p { margin: 0 0 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #99f6e4; }
        .hero h1 { margin: 0; font-size: 22px; line-height: 1.25; }
        .body { padding: 22px 24px; font-size: 14px; line-height: 1.6; color: #334155; }
        .meta { margin: 0 0 8px; }
        .ok { color: #0f766e; font-weight: 700; }
        .warn { color: #b45309; font-weight: 700; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <p>Installation assignment</p>
                <h1>{{ $title }}</h1>
            </div>
            <div class="body">
                <p class="{{ in_array($state, ['accepted', 'rejected'], true) ? 'ok' : 'warn' }}">{{ $message }}</p>
                @if ($questionnaire)
                    <p class="meta"><strong>Customer:</strong> {{ $questionnaire->full_name }}</p>
                    <p class="meta"><strong>Address:</strong> {{ str_replace("\n", ', ', $questionnaire->formatted_address) }}</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
