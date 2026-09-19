<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete installation · {{ $questionnaire->full_name }}</title>
    @include('partials.favicon')
    <style>
        body { margin: 0; background: #f4fbfb; color: #073b4c; font-family: Arial, Helvetica, sans-serif; }
        .wrap { max-width: 680px; margin: 32px auto; padding: 0 16px 48px; }
        .card { background: #fff; border: 1px solid #d7f3ef; border-radius: 14px; overflow: hidden; }
        .hero { padding: 24px; background: linear-gradient(135deg, #041f1e 0%, #073b4c 100%); color: #fff; }
        .hero p { margin: 0 0 7px; color: #99f6e4; font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        .hero h1 { margin: 0; font-size: 26px; }
        .body { padding: 24px; }
        .muted { color: #64748b; font-size: 14px; line-height: 1.6; }
        .waiver { margin: 20px 0; padding: 16px; border: 1px solid #d7f3ef; border-radius: 10px; background: #f8fffe; color: #334155; font-size: 14px; line-height: 1.65; }
        label { display: block; margin: 16px 0 6px; font-size: 13px; font-weight: 700; }
        input[type=text] { width: 100%; box-sizing: border-box; border: 1px solid #b9e5df; border-radius: 8px; padding: 11px 12px; font: inherit; }
        input[type=date], textarea { width: 100%; box-sizing: border-box; border: 1px solid #b9e5df; border-radius: 8px; padding: 11px 12px; font: inherit; }
        textarea { min-height: 96px; resize: vertical; }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
        .field-full { grid-column: 1 / -1; }
        .check { display: flex; align-items: flex-start; gap: 9px; margin-top: 18px; font-size: 14px; font-weight: 400; line-height: 1.5; }
        .check input { margin-top: 3px; }
        .signature-wrap { border: 1px solid #b9e5df; border-radius: 8px; background: #fff; overflow: hidden; }
        canvas { display: block; width: 100%; height: 180px; touch-action: none; }
        .signature-actions { display: flex; justify-content: flex-end; border-top: 1px solid #d7f3ef; padding: 7px; }
        .clear { border: 0; background: transparent; color: #0f766e; cursor: pointer; font-size: 12px; font-weight: 700; }
        .error { color: #b91c1c; font-size: 13px; }
        button.submit { margin-top: 20px; border: 0; border-radius: 999px; padding: 12px 20px; background: #059669; color: #fff; cursor: pointer; font-size: 14px; font-weight: 700; }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <header class="hero">
                <p>Installation completion</p>
                <h1>{{ $questionnaire->full_name }}</h1>
            </header>
            <div class="body">
                @if ($installation->status === \App\Enums\InstallerInstallationStatus::Completed)
                    <p class="muted">This installation was completed on {{ $installation->completed_at?->format('M j, Y g:i A') }}.</p>
                @else
                    <p class="muted">Please have the customer read the waiver below and sign to confirm the installation is complete.</p>
                    <div class="waiver">
                        I confirm that the installation work has been completed and that the installed H2 water system was explained to me. I have had the opportunity to ask questions, understand the basic operation and care of the system, and acknowledge that the installer has completed the work to my satisfaction.
                    </div>

                    <form method="POST" action="{{ $storeUrl }}" id="completion-form">
                        @csrf
                        <div class="field-grid">
                            <div>
                                <label for="completion_installer_name">Installer name</label>
                                <input id="completion_installer_name" name="completion_installer_name" required type="text" value="{{ old('completion_installer_name', $installer->name) }}">
                                @error('completion_installer_name')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="installation_date">Date of installation</label>
                                <input id="installation_date" name="installation_date" required type="date" value="{{ old('installation_date', optional($installation->scheduled_at)->format('Y-m-d')) }}">
                                @error('installation_date')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-full">
                                <label for="completion_address">Customer address</label>
                                <textarea id="completion_address" name="completion_address" required>{{ old('completion_address', $questionnaire->formatted_address) }}</textarea>
                                @error('completion_address')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-full">
                                <label for="installed_product">Product installed</label>
                                <input id="installed_product" name="installed_product" required type="text" placeholder="H2 water system model" value="{{ old('installed_product') }}">
                                @error('installed_product')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-full">
                                <label for="completion_details">Installation details</label>
                                <textarea id="completion_details" name="completion_details" placeholder="Work completed, setup details, testing, and customer walkthrough information...">{{ old('completion_details') }}</textarea>
                                @error('completion_details')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-full">
                                <label for="installer_completion_notes">Installer notes</label>
                                <textarea id="installer_completion_notes" name="installer_completion_notes" placeholder="Any follow-up, observations, or notes for the installation team...">{{ old('installer_completion_notes') }}</textarea>
                                @error('installer_completion_notes')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field-full">
                                <label for="customer_name">Customer full name</label>
                                <input id="customer_name" name="customer_name" required type="text" value="{{ old('customer_name', $questionnaire->full_name) }}">
                                @error('customer_name')<p class="error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <label for="signature-canvas">Customer signature</label>
                        <div class="signature-wrap">
                            <canvas id="signature-canvas" aria-label="Customer signature"></canvas>
                            <div class="signature-actions"><button class="clear" id="clear-signature" type="button">Clear signature</button></div>
                        </div>
                        <input id="signature_data" name="signature_data" type="hidden">
                        @error('signature_data')<p class="error">{{ $message }}</p>@enderror

                        <label class="check" for="waiver_agreed">
                            <input id="waiver_agreed" name="waiver_agreed" required type="checkbox" value="1" @checked(old('waiver_agreed'))>
                            <span>The customer confirms they have read and agree to the completion statement above.</span>
                        </label>
                        @error('waiver_agreed')<p class="error">{{ $message }}</p>@enderror

                        <button class="submit" type="submit">Save customer signature and complete installation</button>
                    </form>
                @endif
            </div>
        </section>
    </main>
    <script>
        const canvas = document.getElementById('signature-canvas');
        const form = document.getElementById('completion-form');
        if (canvas && form) {
            const context = canvas.getContext('2d');
            let drawing = false;
            let hasSignature = false;

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const bounds = canvas.getBoundingClientRect();
                canvas.width = bounds.width * ratio;
                canvas.height = bounds.height * ratio;
                context.scale(ratio, ratio);
                context.lineWidth = 2;
                context.lineCap = 'round';
                context.strokeStyle = '#073b4c';
            }

            function point(event) {
                const bounds = canvas.getBoundingClientRect();
                return { x: event.clientX - bounds.left, y: event.clientY - bounds.top };
            }

            function start(event) { event.preventDefault(); drawing = true; const position = point(event); context.beginPath(); context.moveTo(position.x, position.y); }
            function draw(event) { if (!drawing) return; event.preventDefault(); const position = point(event); context.lineTo(position.x, position.y); context.stroke(); hasSignature = true; }
            function stop() { drawing = false; }

            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', draw);
            canvas.addEventListener('pointerup', stop);
            canvas.addEventListener('pointerleave', stop);
            document.getElementById('clear-signature').addEventListener('click', () => { context.clearRect(0, 0, canvas.width, canvas.height); hasSignature = false; });
            form.addEventListener('submit', (event) => {
                if (!hasSignature) { event.preventDefault(); window.alert('Please ask the customer to sign before completing the installation.'); return; }
                document.getElementById('signature_data').value = canvas.toDataURL('image/png');
            });
        }
    </script>
</body>
</html>
