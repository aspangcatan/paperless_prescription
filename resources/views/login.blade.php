@php
    $steps = [
        ['prescription2', 'Prescribe', 'Doctor', 'Antimicrobial orders with indication, cultures and HOMIS patient details.'],
        ['shield-check', 'Stewardship review', 'IPCC', 'Restricted and monitored drugs are approved or returned with remarks.'],
        ['capsule', 'Dispense', 'Pharmacy', 'Quantities rendered, issuances tracked, returned medicines received.'],
        ['clipboard2-pulse', 'Receive', 'Nurse', 'The ward confirms receipt and the prescription cycle closes.'],
        ['bar-chart-line', 'Monitor & audit', 'AMS committee', 'Every step logged, with AMS, monitored and concurrent-therapy summaries.'],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CSMC E-AMSP: the paperless antimicrobial prescription and stewardship system of Cebu South Medical Center.">
    <title>Sign in · CSMC E-AMSP</title>
    <link rel="icon" href="{{ asset('images/csmc-logo.png') }}" type="image/png">

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
</head>
<body>

<div class="split">
    <aside class="story">
        {{-- Photo: Luis Melendez on Unsplash (Unsplash License). --}}
        <img class="story-photo" src="{{ asset('images/landing/hero-team-corridor.jpg') }}" alt="">

        <div class="story-inner">
            <div class="brand">
                <span class="brand-logo"><img src="{{ asset('images/csmc-logo.png') }}" alt="Cebu South Medical Center logo"></span>
                <span class="brand-text">
                    <span class="brand-org">Cebu South Medical Center</span>
                    <span class="brand-name">E-AMSP · Paperless Antimicrobial Prescription</span>
                </span>
            </div>

            <div class="story-copy">
                <span class="chip"><i class="bi bi-shield-plus"></i> Antimicrobial Stewardship Program</span>
                <h1>Every antimicrobial prescribed right. <span>Every dose accounted for.</span></h1>
                <p class="lead">
                    One paperless prescription moves from the doctor to the IPCC, pharmacy and the ward,
                    with every step on record.
                </p>
            </div>

            <ol class="timeline">
                @foreach($steps as [$icon, $title, $role, $text])
                    <li>
                        <span class="timeline-icon"><i class="bi bi-{{ $icon }}"></i></span>
                        <div>
                            <p class="timeline-title">{{ $title }} <span>{{ $role }}</span></p>
                            <p class="timeline-text">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </aside>

    <main class="auth">
        <div class="auth-inner">
            <div class="auth-head">
                <p class="eyebrow">Staff portal</p>
                <h2>Sign in to E-AMSP</h2>
                <p class="muted">Use your hospital username and password.</p>
            </div>

            @if(Session::has('message'))
                <p class="alert" role="alert">
                    <i class="bi bi-exclamation-circle"></i> {{ Session::get('message') }}
                </p>
            @endif

            <form method="post" action="{{ route('authenticate') }}" id="login-form">
                @csrf
                <div class="field">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <i class="bi bi-person"></i>
                        <input type="text" id="username" name="username" autocomplete="username"
                               placeholder="Enter your username" required autofocus>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" autocomplete="current-password"
                               placeholder="Enter your password" required>
                        <button type="button" class="reveal" id="toggle-password"
                                aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <span>Sign in</span> <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="roles">
                <p class="muted">One account for every role</p>
                <ul>
                    <li><i class="bi bi-prescription2"></i> Doctor</li>
                    <li><i class="bi bi-shield-check"></i> IPCC</li>
                    <li><i class="bi bi-capsule"></i> Pharmacist</li>
                    <li><i class="bi bi-clipboard2-pulse"></i> Nurse</li>
                </ul>
            </div>

            <p class="footnote">&copy; {{ date('Y') }} Cebu South Medical Center · E-AMSP</p>
        </div>
    </main>
</div>

<script>
    (function () {
        var password = document.getElementById('password');
        var toggle = document.getElementById('toggle-password');

        toggle.addEventListener('click', function () {
            var show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show);
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        document.getElementById('login-form').addEventListener('submit', function () {
            var button = this.querySelector('button[type=submit]');
            button.disabled = true;
            button.querySelector('span').textContent = 'Signing in…';
        });
    })();
</script>
</body>
</html>
