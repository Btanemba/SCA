@extends(backpack_view('layouts.auth'))

@section('content')
    <main class="sc-login-page">
        <section class="sc-form-panel" aria-label="Sign in">
            <div class="sc-login-shell">
                <a class="sc-brand" href="{{ url('/') }}" aria-label="SpringCare Academy home">
                    <img src="{{ asset('images/logo.png') }}" alt="SpringCare Academy">
                    <span>SpringCare <strong>Academy</strong></span>
                </a>
                <div class="sc-form-wrap">
                <p class="sc-form-eyebrow">SCHOOL PORTAL</p>
                @include(backpack_view('auth.login.inc.form'))
                @if (config('backpack.base.registration_open'))
                    <p class="sc-register-link">
                        New to the portal?
                        <a tabindex="6" href="{{ route('backpack.auth.register') }}">Create an account</a>
                    </p>
                @endif
                </div>
                <p class="sc-form-footer">SpringCare Academy <span aria-hidden="true">/</span> Secure staff access</p>
            </div>
        </section>
    </main>

    <style>
        .sc-login-page {
            --sc-forest: #173d31;
            --sc-leaf: #367653;
            --sc-ink: #1d3029;
            --sc-muted: #78847e;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(360px, 0.92fr) minmax(480px, 1.08fr);
            color: var(--sc-ink);
            background: #f7f8f4;
            font-family: 'Instrument Sans', 'Segoe UI', sans-serif;
        }

        .sc-welcome-panel {
            position: relative;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            overflow: hidden;
            padding: clamp(32px, 5vw, 76px);
            color: #f7f5e9;
            background-color: var(--sc-forest);
            background-image: repeating-linear-gradient(135deg, transparent 0, transparent 46px, rgba(255, 255, 255, .035) 47px, rgba(255, 255, 255, .035) 48px);
        }

        .sc-brand,
        .sc-mobile-brand {
            display: flex;
            align-items: center;
            gap: 13px;
            color: inherit;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }

        .sc-brand img,
        .sc-mobile-brand img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: contain;
            background: #fff;
        }

        .sc-brand strong,
        .sc-mobile-brand strong {
            display: block;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .72;
        }

        .sc-welcome-copy {
            position: relative;
            z-index: 1;
            width: min(100%, 520px);
            margin: auto 0;
            padding: 64px 0 96px;
        }

        .sc-eyebrow,
        .sc-form-eyebrow {
            margin: 0 0 24px;
            color: #c6d79c;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .18em;
        }

        .sc-welcome-copy h1 {
            max-width: 540px;
            margin: 0;
            color: #fffdf4;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(44px, 5.2vw, 74px);
            font-weight: 400;
            line-height: 1.04;
        }

        .sc-welcome-copy h1 em {
            color: #d2df9b;
            font-weight: 400;
        }

        .sc-welcome-note {
            max-width: 365px;
            margin: 25px 0 0;
            color: rgba(255, 253, 244, .73);
            font-size: 15px;
            line-height: 1.8;
        }

        .sc-pattern {
            position: absolute;
            right: -36px;
            bottom: 82px;
            display: flex;
            width: min(50%, 310px);
            min-width: 200px;
            flex-direction: column;
            align-items: flex-start;
            color: rgba(210, 223, 155, .16);
            transform: rotate(-8deg);
        }

        .sc-pattern-line {
            width: 100%;
            height: 1px;
            background: currentColor;
        }

        .sc-pattern-word {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(60px, 8vw, 116px);
            line-height: 1;
        }

        .sc-pattern-caption {
            align-self: flex-end;
            font-size: 10px;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .sc-panel-footer,
        .sc-form-footer {
            margin: 0;
            color: rgba(255, 253, 244, .58);
            font-size: 11px;
            letter-spacing: .04em;
        }

        .sc-form-panel {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 64px 40px 30px;
        }

        .sc-form-wrap {
            width: min(100%, 390px);
            margin: auto 0;
        }

        .sc-mobile-brand {
            display: none;
            color: var(--sc-forest);
        }

        .sc-form-eyebrow {
            margin-bottom: 14px;
            color: var(--sc-leaf);
        }

        .sc-form-wrap .h2 {
            margin: 0 0 34px !important;
            color: var(--sc-ink);
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 38px;
            font-weight: 400;
            text-align: left !important;
        }

        .sc-form-wrap .form-label {
            margin-bottom: 8px;
            color: #394a42;
            font-size: 13px;
            font-weight: 500;
        }

        .sc-form-wrap .form-control {
            min-height: 50px;
            border: 1px solid #d8dfd9;
            border-radius: 4px;
            background: #fff;
            box-shadow: none;
            color: var(--sc-ink);
            font-size: 15px;
        }

        .sc-form-wrap .form-control:focus {
            border-color: #6d9a77;
            box-shadow: 0 0 0 3px rgba(76, 135, 88, .13);
        }

        .sc-form-wrap .mb-3 {
            margin-bottom: 22px !important;
        }

        .sc-form-wrap .mb-2 {
            margin-bottom: 14px !important;
        }

        .sc-form-wrap .form-check-label,
        .sc-form-wrap .form-label-description {
            color: #647169;
            font-size: 12px;
        }

        .sc-form-wrap .form-label-description a,
        .sc-register-link a {
            color: var(--sc-leaf);
            text-decoration: none;
        }

        .sc-form-wrap .form-label-description a:hover,
        .sc-register-link a:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .sc-form-wrap .form-footer {
            margin-top: 26px;
        }

        .sc-form-wrap .btn-primary {
            min-height: 50px;
            border: 0;
            border-radius: 4px;
            background: var(--sc-leaf);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            transition: background-color .18s ease, transform .18s ease;
        }

        .sc-form-wrap .btn-primary:hover {
            background: #285d40;
            transform: translateY(-1px);
        }

        .sc-register-link {
            margin: 25px 0 0;
            color: var(--sc-muted);
            font-size: 13px;
            text-align: center;
        }

        .sc-form-footer {
            color: #87928b;
            text-align: center;
        }

        .sc-form-footer span {
            padding: 0 7px;
            color: #b4bdb5;
        }

        @media (max-width: 800px) {
            .sc-login-page {
                display: block;
                min-height: 100vh;
                background: #f7f8f4;
            }

            .sc-welcome-panel {
                display: none;
            }

            .sc-form-panel {
                min-height: 100vh;
                padding: 36px 24px 22px;
            }

            .sc-form-wrap {
                width: min(100%, 420px);
            }

            .sc-mobile-brand {
                display: flex;
                margin-bottom: 55px;
            }

            .sc-form-eyebrow {
                margin-bottom: 12px;
            }

            .sc-form-wrap .h2 {
                margin-bottom: 30px !important;
                font-size: 34px;
            }
        }

        @media (max-width: 420px) {
            .sc-form-panel {
                padding-right: 20px;
                padding-left: 20px;
            }

            .sc-mobile-brand {
                margin-bottom: 42px;
            }

            .sc-form-wrap .h2 {
                font-size: 32px;
            }
        }

        .sc-login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            background-color: #f4f6f1;
            background-image: linear-gradient(rgba(23, 61, 49, .025) 1px, transparent 1px), linear-gradient(90deg, rgba(23, 61, 49, .025) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .sc-form-panel {
            width: 100%;
            min-height: 0;
            padding: 0;
        }

        .sc-login-shell {
            width: min(100%, 500px);
            margin: auto;
        }

        .sc-brand {
            flex-direction: column;
            gap: 13px;
            justify-content: center;
            margin: 0 0 30px;
            color: var(--sc-forest);
            text-align: center;
        }

        .sc-brand img {
            width: 116px;
            height: 116px;
            padding: 11px;
            border: 1px solid rgba(23, 61, 49, .12);
            border-radius: 26px;
            box-shadow: 0 12px 30px rgba(23, 61, 49, .12);
        }

        .sc-brand span {
            font-size: 18px;
            line-height: 1.2;
        }

        .sc-brand strong {
            margin-top: 4px;
            font-size: 10px;
            letter-spacing: .18em;
            opacity: .7;
        }

        .sc-form-wrap {
            width: 100%;
            margin: 0;
            padding: 46px 52px 42px;
            border: 1px solid #dce4da;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(28, 61, 44, .13);
        }

        .sc-form-wrap .h2 {
            margin-bottom: 30px !important;
            font-size: 36px;
            text-align: center !important;
        }

        .sc-form-footer {
            margin-top: 22px;
            color: #728078;
        }

        @media (max-width: 800px) {
            .sc-login-page {
                padding: 32px 20px;
            }

            .sc-form-panel {
                min-height: 0;
                padding: 0;
            }

            .sc-brand {
                display: flex;
                gap: 10px;
                margin-bottom: 22px;
            }

            .sc-brand img {
                width: 92px;
                height: 92px;
                padding: 9px;
                border-radius: 22px;
            }

            .sc-form-wrap {
                width: 100%;
                padding: 38px 36px 34px;
            }

            .sc-form-wrap .h2 {
                font-size: 34px;
            }
        }

        @media (max-width: 420px) {
            .sc-brand img {
                width: 80px;
                height: 80px;
                padding: 8px;
            }

            .sc-form-wrap {
                padding: 32px 24px 28px;
            }
        }
    </style>
@endsection