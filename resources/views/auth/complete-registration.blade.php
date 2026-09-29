<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#235b48">
    <meta name="robots" content="noindex, nofollow">
    <title>Complete your registration | Springcare Academy</title>
    @vite('resources/css/home.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #172b25;
            --muted: #52645d;
            --paper: #f5f4ed;
            --green: #235b48;
            --lime: #d6e756;
            --line: rgba(23, 43, 37, .14);
        }

        * { box-sizing: border-box; }

        body.reg-body {
            align-items: center;
            background:
                radial-gradient(1100px 600px at 12% -10%, rgba(214, 231, 86, .35), transparent 60%),
                radial-gradient(900px 620px at 100% 110%, rgba(35, 91, 72, .22), transparent 60%),
                var(--paper);
            color: var(--ink);
            display: flex;
            font-family: 'DM Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .reg-shell {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: 0 40px 90px -40px rgba(23, 43, 37, .45);
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            max-width: 980px;
            overflow: hidden;
            width: 100%;
        }

        .reg-aside {
            background: linear-gradient(155deg, #2c6e57 0%, var(--green) 48%, #143a2e 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px 40px;
            position: relative;
        }

        .reg-aside::after {
            background: radial-gradient(420px 320px at 85% 8%, rgba(214, 231, 86, .28), transparent 70%);
            content: '';
            inset: 0;
            pointer-events: none;
            position: absolute;
        }

        .reg-brand {
            align-items: center;
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .reg-brand-mark {
            align-items: center;
            background: var(--lime);
            border-radius: 14px;
            color: var(--green);
            display: flex;
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.15rem;
            font-weight: 600;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .reg-brand-name { font-weight: 700; letter-spacing: .02em; }

        .reg-aside-copy { position: relative; z-index: 1; }

        .reg-aside h2 {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(1.7rem, 2.6vw, 2.25rem);
            font-weight: 600;
            line-height: 1.15;
            margin: 0 0 12px;
        }

        .reg-aside p {
            color: rgba(255, 255, 255, .78);
            line-height: 1.55;
            margin: 0 0 26px;
            max-width: 34ch;
        }

        .reg-steps { display: grid; gap: 14px; list-style: none; margin: 0; padding: 0; }

        .reg-steps li {
            align-items: center;
            color: rgba(255, 255, 255, .85);
            display: flex;
            font-size: .94rem;
            gap: 12px;
        }

        .reg-steps span {
            align-items: center;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 50%;
            display: flex;
            flex: 0 0 26px;
            font-size: .8rem;
            font-weight: 700;
            height: 26px;
            justify-content: center;
            width: 26px;
        }

        .reg-steps li.is-current { color: #fff; font-weight: 600; }
        .reg-steps li.is-current span { background: var(--lime); border-color: var(--lime); color: var(--green); }

        .reg-aside-foot {
            border-top: 1px solid rgba(255, 255, 255, .16);
            color: rgba(255, 255, 255, .6);
            font-size: .82rem;
            padding-top: 18px;
            position: relative;
            z-index: 1;
        }

        .reg-main { padding: 48px 44px; }

        .reg-eyebrow {
            color: var(--green);
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .16em;
            margin: 0 0 10px;
            text-transform: uppercase;
        }

        .reg-main h1 {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(1.55rem, 2.4vw, 1.95rem);
            font-weight: 600;
            line-height: 1.2;
            margin: 0 0 8px;
        }

        .reg-lead { color: var(--muted); line-height: 1.55; margin: 0 0 28px; }

        .reg-errors {
            background: #fdecec;
            border: 1px solid #f2c9c9;
            border-radius: 14px;
            color: #8e2b2b;
            font-size: .9rem;
            list-style: none;
            margin: 0 0 22px;
            padding: 14px 18px;
        }

        .reg-errors li + li { margin-top: 6px; }

        .reg-field { margin-bottom: 20px; }

        .reg-field label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .reg-input-wrap { position: relative; }

        .reg-field input {
            background: #fff;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            color: var(--ink);
            font: inherit;
            padding: 13px 15px;
            transition: border-color .15s ease, box-shadow .15s ease;
            width: 100%;
        }

        .reg-field input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 4px rgba(35, 91, 72, .12);
            outline: none;
        }

        .reg-input-wrap.has-toggle input { padding-right: 78px; }

        .reg-toggle {
            background: none;
            border: 0;
            color: var(--green);
            cursor: pointer;
            font: inherit;
            font-size: .8rem;
            font-weight: 700;
            padding: 4px 8px;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .reg-toggle:hover { text-decoration: underline; }

        .reg-email-row {
            align-items: center;
            background: #f3f4ef;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            display: flex;
            gap: 10px;
            padding: 12px 15px;
        }

        .reg-email-row input {
            background: none;
            border: 0;
            color: var(--muted);
            cursor: default;
            flex: 1;
            font: inherit;
            min-width: 0;
            padding: 0;
        }

        .reg-email-row input:focus { box-shadow: none; outline: none; }

        .reg-verified {
            align-items: center;
            background: rgba(35, 91, 72, .1);
            border-radius: 999px;
            color: var(--green);
            display: inline-flex;
            flex: none;
            font-size: .72rem;
            font-weight: 700;
            gap: 5px;
            padding: 4px 10px;
        }

        .reg-meter { display: flex; gap: 5px; margin-top: 10px; }

        .reg-meter i {
            background: var(--line);
            border-radius: 999px;
            flex: 1;
            height: 5px;
            transition: background .2s ease;
        }

        .reg-meter[data-score="1"] i:nth-child(-n+1) { background: #d96b5b; }
        .reg-meter[data-score="2"] i:nth-child(-n+2) { background: #e0a94f; }
        .reg-meter[data-score="3"] i:nth-child(-n+3) { background: #9bbf4d; }
        .reg-meter[data-score="4"] i { background: var(--green); }

        .reg-hint {
            color: var(--muted);
            font-size: .82rem;
            margin: 8px 0 0;
            min-height: 1.2em;
        }

        .reg-hint.is-error { color: #a83c3c; }
        .reg-hint.is-ok { color: var(--green); }

        .reg-submit {
            align-items: center;
            background: var(--green);
            border: 0;
            border-radius: 14px;
            color: #fff;
            cursor: pointer;
            display: flex;
            font: inherit;
            font-weight: 700;
            gap: 10px;
            justify-content: center;
            margin-top: 8px;
            padding: 15px 20px;
            transition: transform .12s ease, box-shadow .2s ease, background .2s ease;
            width: 100%;
        }

        .reg-submit:hover:not(:disabled) {
            background: #1c4c3c;
            box-shadow: 0 14px 28px -14px rgba(35, 91, 72, .9);
            transform: translateY(-1px);
        }

        .reg-submit:disabled { background: #9aa8a2; cursor: not-allowed; }

        .reg-foot {
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.5;
            margin-top: 26px;
            padding-top: 18px;
        }

        @media (max-width: 820px) {
            .reg-shell { grid-template-columns: 1fr; }
            .reg-aside { gap: 28px; padding: 34px 30px; }
            .reg-aside p { margin-bottom: 0; }
            .reg-main { padding: 34px 28px 38px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body class="reg-body">
    <main class="reg-shell">
        <aside class="reg-aside">
            <div class="reg-brand">
                <span class="reg-brand-mark">SC</span>
                <span class="reg-brand-name">Springcare Academy</span>
            </div>

            <div class="reg-aside-copy">
                <h2>One last step to your account.</h2>
                <p>Set a password and you'll be taken straight into your Springcare dashboard.</p>

                <ol class="reg-steps">
                    <li><span>&#10003;</span> Invitation sent by the school</li>
                    <li><span>&#10003;</span> Email address confirmed</li>
                    <li class="is-current"><span>3</span> Choose your password</li>
                </ol>
            </div>

            <p class="reg-aside-foot">This link is unique to you. Please don't forward it to anyone.</p>
        </aside>

        <div class="reg-main">
            <p class="reg-eyebrow">Secure setup</p>
            <h1>Complete your registration</h1>
            <p class="reg-lead">Choose a password to finish setting up your account.</p>

            @if ($errors->any())
                <ul class="reg-errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('registration.complete.store') }}" id="reg-form" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="reg-field">
                    <label for="email">Your email</label>
                    <div class="reg-email-row">
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" readonly>
                        <span class="reg-verified">&#10003; Verified</span>
                    </div>
                </div>

                <div class="reg-field">
                    <label for="password">Create password</label>
                    <div class="reg-input-wrap has-toggle">
                        <input id="password" type="password" name="password" autocomplete="new-password" required autofocus>
                        <button type="button" class="reg-toggle" data-target="password" aria-label="Show password">Show</button>
                    </div>
                    <div class="reg-meter" id="reg-meter" data-score="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    <p class="reg-hint" id="reg-strength" aria-live="polite">Use at least 8 characters.</p>
                </div>

                <div class="reg-field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="reg-input-wrap has-toggle">
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                        <button type="button" class="reg-toggle" data-target="password_confirmation" aria-label="Show password">Show</button>
                    </div>
                    <p class="reg-hint" id="reg-match" aria-live="polite"></p>
                </div>

                <button type="submit" class="reg-submit" id="reg-submit" disabled>Set password and sign in &rarr;</button>
            </form>

            <p class="reg-foot">Didn't expect this invitation? Ignore this page and the link will expire on its own.</p>
        </div>
    </main>

    <script>
    (() => {
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const meter = document.getElementById('reg-meter');
        const strength = document.getElementById('reg-strength');
        const match = document.getElementById('reg-match');
        const submit = document.getElementById('reg-submit');
        const labels = ['Use at least 8 characters.', 'Weak password', 'Getting better', 'Strong password', 'Excellent password'];

        function score(value) {
            if (value.length < 8) return 0;
            let points = 1;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) points++;
            if (/\d/.test(value)) points++;
            if (/[^A-Za-z0-9]/.test(value) || value.length >= 14) points++;
            return Math.min(points, 4);
        }

        function refresh() {
            const value = password.value;
            const level = score(value);

            meter.dataset.score = value ? level : 0;
            strength.textContent = labels[value ? level : 0];
            strength.className = 'reg-hint' + (value && level === 0 ? ' is-error' : '');

            const filled = confirmation.value.length > 0;
            const same = filled && value === confirmation.value;
            match.textContent = filled ? (same ? 'Passwords match.' : 'Passwords do not match yet.') : '';
            match.className = 'reg-hint' + (filled ? (same ? ' is-ok' : ' is-error') : '');

            submit.disabled = !(value.length >= 8 && same);
        }

        [password, confirmation].forEach((input) => input.addEventListener('input', refresh));

        document.querySelectorAll('.reg-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const shown = input.type === 'text';
                input.type = shown ? 'password' : 'text';
                button.textContent = shown ? 'Show' : 'Hide';
                button.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
                input.focus();
            });
        });

        document.getElementById('reg-form').addEventListener('submit', (event) => {
            if (submit.disabled) {
                event.preventDefault();
                return;
            }
            submit.textContent = 'Setting up your account…';
        });

        refresh();
    })();
    </script>
</body>
</html>
