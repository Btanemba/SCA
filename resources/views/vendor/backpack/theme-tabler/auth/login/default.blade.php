@extends(backpack_view('layouts.auth'))

@section('content')
    <main class="sc-login-page">
        <section class="sc-welcome-panel" aria-label="Welcome to SpringCare Academy">
            <a class="sc-brand" href="{{ url('/') }}" aria-label="SpringCare Academy home">
                <img src="{{ asset('images/logo.png') }}" alt="">
                <span class="sc-brand-name"><strong>SpringCare</strong><span>Academy</span></span>
            </a>

            <div class="sc-welcome-copy">
                <p class="sc-eyebrow">SPRINGCARE ACADEMY</p>
                <h1>Good to see<br>you again.</h1>
                <p class="sc-welcome-note">Your workspace is ready when you are.</p>
            </div>

            <p class="sc-panel-footer">Learn. Grow. Thrive.</p>
        </section>

        <section class="sc-form-panel" aria-label="Sign in">
            <div class="sc-form-wrap">
                <p class="sc-form-eyebrow">ACCOUNT ACCESS</p>
                @include(backpack_view('auth.login.inc.form'))
                <p class="sc-form-footer">Authorized team members only</p>
            </div>
        </section>
    </main>

    <style>
        .sc-login-page {
            --sc-forest: #164b42;
            --sc-forest-dark: #103f38;
            --sc-ink: #1d302d;
            --sc-muted: #788780;
            display: grid;
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            grid-template-columns: minmax(0, 0.81fr) minmax(0, 1fr);
            color: var(--sc-ink);
            background: #f5f6f2;
            font-family: 'Instrument Sans', 'Segoe UI', sans-serif;
        }

        .sc-welcome-panel {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 100vh;
            min-height: 100svh;
            flex-direction: column;
            overflow: hidden;
            padding: clamp(32px, 5.4vw, 90px);
            color: #fff;
            background:
                radial-gradient(ellipse at 12% 100%, rgba(52, 126, 104, .25), transparent 42%),
                linear-gradient(145deg, #174f46, var(--sc-forest-dark));
        }

        .sc-welcome-panel::after {
            position: absolute;
            right: -125px;
            bottom: 8%;
            width: clamp(300px, 39vw, 470px);
            aspect-ratio: 1;
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 50%;
            box-shadow: 0 0 0 38px rgba(255, 255, 255, .035), 0 0 0 76px rgba(255, 255, 255, .025);
            content: '';
            pointer-events: none;
        }

        .sc-brand {
            position: relative;
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 13px;
            color: #fff;
            z-index: 1;
            text-decoration: none;
        }

        .sc-brand img {
            width: 48px;
            height: 48px;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .14);
            object-fit: cover;
        }

        .sc-brand-name {
            display: flex;
            flex-direction: column;
            gap: 1px;
            font-size: 15px;
            letter-spacing: .01em;
        }

        .sc-brand-name strong {
            font-weight: 700;
        }

        .sc-brand-name span {
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .sc-welcome-copy {
            position: relative;
            z-index: 1;
            width: min(100%, 520px);
            margin: auto 0;
            padding: 48px 0;
        }

        .sc-eyebrow,
        .sc-form-eyebrow {
            margin: 0 0 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .1em;
        }

        .sc-eyebrow {
            color: #f28a6b;
        }

        .sc-welcome-copy::before {
            display: block;
            width: 38px;
            height: 3px;
            margin-bottom: 22px;
            border-radius: 3px;
            background: linear-gradient(90deg, #f3a277 0 58%, rgba(255, 255, 255, .35) 58%);
            content: '';
        }

        .sc-welcome-copy h1 {
            margin: 0;
            color: #fff;
            font-size: clamp(46px, 5.1vw, 74px);
            font-weight: 600;
            letter-spacing: -.045em;
            line-height: 1.08;
        }

        .sc-welcome-note {
            margin: 24px 0 0;
            color: rgba(255, 255, 255, .7);
            font-size: 16px;
            line-height: 1.6;
        }

        .sc-panel-footer {
            position: relative;
            z-index: 1;
            margin: 0;
            color: rgba(255, 255, 255, .68);
            font-size: 13px;
        }

        .sc-form-panel {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 100vh;
            min-height: 100svh;
            align-items: center;
            justify-content: center;
            padding: 64px clamp(32px, 6vw, 96px);
        }

        .sc-form-panel::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 5px;
            background: linear-gradient(90deg, #164b42 0%, #347b62 58%, #e4a344 100%);
            content: '';
        }

        .sc-form-wrap {
            width: min(100%, 470px);
        }

        .sc-form-eyebrow {
            margin-bottom: 10px;
            color: #7e8d86;
            font-size: 12px;
            letter-spacing: .08em;
        }

        .sc-form-wrap .h2 {
            margin: 0 0 38px !important;
            color: var(--sc-ink);
            font-family: 'Instrument Sans', 'Segoe UI', sans-serif;
            font-size: 34px;
            font-weight: 600;
            letter-spacing: -.035em;
            text-align: left !important;
        }

        .sc-form-wrap .form-label {
            margin-bottom: 9px;
            color: #40534c;
            font-size: 14px;
            font-weight: 600;
        }

        .sc-form-wrap .form-control {
            min-height: 54px;
            border: 1px solid #dce3de;
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(20, 45, 37, .04);
            color: var(--sc-ink);
            font-size: 15px;
        }

        .sc-form-wrap .form-control:focus {
            border-color: #6b9b8a;
            box-shadow: 0 0 0 3px rgba(48, 119, 96, .13);
        }

        .sc-form-wrap .mb-3 {
            margin-bottom: 24px !important;
        }

        .sc-form-wrap .mb-2 {
            margin-bottom: 14px !important;
        }

        .sc-form-wrap .form-check-label,
        .sc-form-wrap .form-label-description {
            color: #52635c;
            font-size: 14px;
        }

        .sc-form-wrap .form-label-description a {
            color: #326b60;
            text-decoration: none;
        }

        .sc-form-wrap .form-label-description a:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .sc-form-wrap .form-footer {
            margin-top: 30px;
        }

        .sc-form-wrap .btn-primary {
            min-height: 56px;
            border: 0;
            border-radius: 9px;
            background: var(--sc-forest);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            transition: background-color .18s ease, transform .18s ease;
        }

        .sc-form-wrap .btn-primary:hover {
            background: #103f38;
            transform: translateY(-1px);
        }

        .sc-form-wrap .btn-primary:focus-visible {
            outline: 3px solid rgba(48, 119, 96, .35);
            outline-offset: 3px;
        }

        .sc-form-footer {
            margin: 28px 0 0;
            color: var(--sc-muted);
            font-size: 13px;
        }

        @media (max-width: 800px) {
            .sc-login-page {
                grid-template-columns: minmax(0, 1fr);
            }

            .sc-welcome-panel {
                min-height: 265px;
                padding: 28px clamp(24px, 7vw, 56px);
            }

            .sc-welcome-panel::after {
                right: -105px;
                bottom: -240px;
                width: 360px;
            }

            .sc-brand {
                gap: 10px;
            }

            .sc-brand img {
                width: 42px;
                height: 42px;
                border-radius: 10px;
            }

            .sc-welcome-copy {
                margin: 24px 0 0;
                padding: 0 0 20px;
            }

            .sc-eyebrow {
                margin-bottom: 9px;
                font-size: 10px;
            }

            .sc-welcome-copy h1 {
                font-size: clamp(32px, 7vw, 46px);
            }

            .sc-welcome-note,
            .sc-panel-footer {
                display: none;
            }

            .sc-form-panel {
                min-height: 0;
                padding: 44px 24px 52px;
            }
        }

        @media (max-width: 420px) {
            .sc-welcome-panel {
                min-height: 230px;
            }

            .sc-form-panel {
                padding: 36px 22px 44px;
            }

            .sc-form-wrap .h2 {
                margin-bottom: 30px !important;
                font-size: 31px;
            }

            .sc-form-wrap .d-flex.justify-content-between {
                gap: 12px;
            }

            .sc-form-wrap .form-check-label,
            .sc-form-wrap .form-label-description {
                font-size: 12px;
            }
        }
    </style>
@endsection
