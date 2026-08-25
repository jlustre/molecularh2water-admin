<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Decline installation assignment · Molecular H2 Water</title>
    @include('partials.favicon')
    <style>
        body { margin: 0; background: #f4fbfb; color: #073b4c; font-family: Arial, Helvetica, sans-serif; }
        .wrap { max-width: 520px; margin: 48px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #d7f3ef; border-radius: 14px; overflow: hidden; }
        .hero { padding: 22px 24px; background: linear-gradient(135deg, #041f1e 0%, #073b4c 100%); color: #fff; }
        .hero p { margin: 0 0 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #99f6e4; }
        .hero h1 { margin: 0; font-size: 22px; line-height: 1.25; }
        .body { padding: 22px 24px; }
        .hint { margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #334155; }
        .reason { display: block; margin: 0 0 8px; padding: 10px 12px; border: 1px solid #d7f3ef; border-radius: 10px; font-size: 14px; }
        .reason input { margin-right: 8px; }
        label.block { display: block; margin: 16px 0 6px; font-size: 13px; font-weight: 700; }
        textarea { width: 100%; min-height: 90px; box-sizing: border-box; border: 1px solid #d7f3ef; border-radius: 10px; padding: 10px 12px; font: inherit; }
        .error { margin: 8px 0 0; color: #b91c1c; font-size: 13px; }
        .actions { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; }
        button { background: #b45309; color: #fff; border: 0; border-radius: 999px; padding: 11px 18px; font-weight: 700; font-size: 14px; cursor: pointer; }
        a.secondary { display: inline-block; padding: 11px 18px; border-radius: 999px; border: 1px solid #d7f3ef; color: #0f766e; text-decoration: none; font-weight: 700; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <p>Installation assignment</p>
                <h1>Decline this job</h1>
            </div>
            <div class="body">
                <p class="hint">
                    Hi {{ $installer->name }}. Tell us why you cannot take
                    <strong>{{ $questionnaire?->full_name ?: 'this installation' }}</strong>
                    so we can reassign it quickly.
                </p>

                <form method="POST" action="{{ $storeUrl }}">
                    @csrf

                    @foreach ($reasons as $value => $label)
                        <label class="reason">
                            <input type="radio" name="reason" value="{{ $value }}" @checked(old('reason') === $value) required>
                            {{ $label }}
                        </label>
                    @endforeach
                    @error('reason')<p class="error">{{ $message }}</p>@enderror

                    <label class="block" for="notes">Details{{ old('reason') === 'other' ? ' (required)' : ' (optional)' }}</label>
                    <textarea id="notes" name="notes" placeholder="Anything the team should know...">{{ old('notes') }}</textarea>
                    @error('notes')<p class="error">{{ $message }}</p>@enderror

                    <div class="actions">
                        <button type="submit">Submit decline</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
