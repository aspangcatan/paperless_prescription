{{-- resources/views/errors/503.blade.php --}}
{{-- Shown while the app is down: `php artisan down --retry=300 --render="errors::503"`. Colours and type follow login.css. --}}
@php
    $headers = isset($exception) && method_exists($exception, 'getHeaders') ? $exception->getHeaders() : [];
    $retryAfter = isset($headers['Retry-After']) && is_numeric($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Under maintenance · CSMC E-AMSP</title>
    <link rel="icon" href="{{ asset('images/csmc-logo.png') }}" type="image/png">

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary: #00236f;
            --primary-container: #1e3a8a;
            --primary-fixed: #dce1ff;
            --on-primary: #ffffff;
            --secondary: #006a61;
            --secondary-container: #86f2e4;
            --surface-lowest: #ffffff;
            --surface-low: #f2f3ff;
            --on-surface: #131b2e;
            --on-surface-variant: #444651;
            --outline-variant: #c5c5d3;
            --font-body: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --font-head: 'Plus Jakarta Sans', var(--font-body);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            font-family: var(--font-body);
            font-size: 0.875rem;
            line-height: 1.375rem;
            color: var(--on-primary);
            background: var(--primary);
            -webkit-font-smoothing: antialiased;
        }

        h1, p { margin: 0; }

        /* Same navy wash, teal glow and dot grid as the sign-in story panel. */
        body::before,
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        body::before {
            background:
                radial-gradient(60rem 40rem at 110% -10%, rgba(134, 242, 228, 0.22), transparent 60%),
                radial-gradient(40rem 30rem at -10% 110%, rgba(134, 242, 228, 0.10), transparent 60%),
                linear-gradient(160deg, var(--primary-container), var(--primary) 55%);
        }

        body::after {
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: linear-gradient(to bottom, #000, transparent 70%);
            -webkit-mask-image: linear-gradient(to bottom, #000, transparent 70%);
        }

        .page {
            position: relative;
            z-index: 1;
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 2rem;
            width: 100%;
            max-width: 40rem;
            margin: 0 auto;
            padding: 2.5rem 1rem 2rem;
        }

        .brand { display: flex; align-items: center; gap: 0.875rem; }

        .brand-logo {
            display: flex;
            flex-shrink: 0;
            padding: 0.25rem;
            border-radius: 999px;
            background: var(--surface-lowest);
        }

        .brand-logo img { display: block; width: 2.5rem; height: 2.5rem; object-fit: contain; }

        .brand-text { display: flex; flex-direction: column; min-width: 0; }

        .brand-org {
            font-size: 0.6875rem;
            line-height: 1rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--secondary-container);
        }

        .brand-name { font: 600 0.9375rem/1.25rem var(--font-head); }

        .card {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin: auto 0;
            padding: 2.25rem;
            border-radius: 1.25rem;
            color: var(--on-surface);
            background: var(--surface-lowest);
            box-shadow: 0 24px 60px -20px rgba(0, 10, 40, 0.55);
        }

        .card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            font-size: 1.5rem;
            color: var(--primary);
            background: var(--secondary-container);
        }

        .card-icon i { display: inline-block; animation: turn 6s linear infinite; }

        @keyframes turn { to { transform: rotate(360deg); } }

        .chip {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3125rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--secondary);
            background: rgba(134, 242, 228, 0.25);
            box-shadow: inset 0 0 0 1px rgba(0, 106, 97, 0.25);
        }

        .chip .dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: var(--secondary);
            animation: pulse 1.6s ease-in-out infinite;
        }

        @keyframes pulse { 50% { opacity: 0.3; } }

        .card h1 {
            margin-top: 0.75rem;
            font: 700 1.875rem/2.375rem var(--font-head);
            letter-spacing: -0.02em;
            color: var(--primary);
        }

        .card .lead {
            margin-top: 0.5rem;
            font-size: 0.9375rem;
            line-height: 1.5rem;
            color: var(--on-surface-variant);
        }

        .facts {
            display: grid;
            gap: 0.75rem;
            margin: 0;
            padding: 1.125rem;
            list-style: none;
            border-radius: 0.875rem;
            background: var(--surface-low);
        }

        .facts li { display: flex; gap: 0.75rem; }

        .facts i {
            flex-shrink: 0;
            margin-top: 0.0625rem;
            font-size: 1rem;
            color: var(--secondary);
        }

        .facts strong { display: block; font-weight: 600; color: var(--on-surface); }

        .facts span { color: var(--on-surface-variant); }

        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 0;
            border-radius: 0.75rem;
            font: 600 0.875rem/1.25rem var(--font-body);
            color: var(--on-primary);
            background: var(--primary);
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-primary:hover { background: var(--primary-container); }

        .btn-primary:focus-visible { outline: 3px solid var(--secondary-container); outline-offset: 2px; }

        .countdown { font-size: 0.8125rem; color: var(--on-surface-variant); }

        .footnote {
            font-size: 0.75rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }

        @media (max-width: 480px) {
            .page { gap: 1.5rem; padding-top: 1.5rem; }
            .card { padding: 1.5rem; }
            .card h1 { font-size: 1.5rem; line-height: 2rem; }
            .btn-primary { width: 100%; justify-content: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            .card-icon i, .chip .dot { animation: none; }
        }
    </style>
</head>
<body>

<div class="page">
    <div class="brand">
        <span class="brand-logo"><img src="{{ asset('images/csmc-logo.png') }}" alt="Cebu South Medical Center logo"></span>
        <span class="brand-text">
            <span class="brand-org">Cebu South Medical Center</span>
            <span class="brand-name">E-AMSP · Paperless Antimicrobial Prescription</span>
        </span>
    </div>

    <main class="card">
        <span class="card-icon" aria-hidden="true"><i class="bi bi-gear"></i></span>

        <div>
            <span class="chip"><span class="dot" aria-hidden="true"></span> Scheduled maintenance</span>
            <h1>We'll be back shortly</h1>
            <p class="lead">
                E-AMSP is down for a system update. We're applying improvements to keep
                prescriptions, reviews and dispensing running smoothly.
            </p>
        </div>

        <ul class="facts">
            <li>
                <i class="bi bi-shield-check"></i>
                <div><strong>Your records are safe</strong><span>Saved prescriptions and approvals are not affected.</span></div>
            </li>
            <li>
                <i class="bi bi-clock-history"></i>
                <div>
                    <strong>Expected back</strong>
                    <span>{{ $retryAfter ? 'In about ' . max(1, (int) ceil($retryAfter / 60)) . ' minute' . ($retryAfter > 60 ? 's' : '') . '.' : 'Within a few minutes.' }}</span>
                </div>
            </li>
            <li>
                <i class="bi bi-telephone"></i>
                <div><strong>Urgent orders</strong><span>Follow the manual prescription process and contact the IT section.</span></div>
            </li>
        </ul>

        <div class="actions">
            <button type="button" class="btn-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Try again
            </button>
            <p class="countdown" id="countdown" aria-live="polite"></p>
        </div>
    </main>

    <p class="footnote">&copy; {{ date('Y') }} Cebu South Medical Center · E-AMSP</p>
</div>

<script>
    (function () {
        // Re-check on the Retry-After interval, or every minute without one.
        var seconds = {{ $retryAfter ?: 60 }};
        var label = document.getElementById('countdown');

        var tick = function () {
            if (seconds <= 0) { location.reload(); return; }
            var m = Math.floor(seconds / 60), s = seconds % 60;
            label.textContent = 'Checking again in ' + (m ? m + 'm ' : '') + s + 's';
            seconds--;
            setTimeout(tick, 1000);
        };
        tick();
    })();
</script>
</body>
</html>
