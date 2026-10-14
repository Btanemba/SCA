<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Staff | Springcare Academy</title>
    <meta name="description" content="Meet the staff caring for and supporting children at Springcare Academy.">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main class="management-page" id="top">
        <section class="management-hero" aria-labelledby="staff-title">
            <div class="management-hero-inner">
                <p class="management-eyebrow">The people who make it possible</p>
                <h1 id="staff-title">Meet our staff.</h1>
                <p class="management-hero-copy">The caring team supporting every child's learning, wellbeing, and day at Springcare Academy.</p>
            </div>
        </section>

        <section class="management-content" aria-labelledby="staff-team-title">
            <div class="management-content-inner">
                <div class="management-section-heading">
                    <p class="management-eyebrow">Springcare Academy</p>
                    <h2 id="staff-team-title">Our staff team</h2>
                </div>

                @if ($staffMembers->isEmpty())
                    <p class="management-empty">Staff profiles will be published here soon.</p>
                @else
                    <ul class="management-grid">
                        @foreach ($staffMembers as $member)
                            <li>
                                @include('staffs.partials.member-card', ['member' => $member])
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>Springcare Academy</p>
        <p>&copy; {{ date('Y') }} Springcare Academy</p>
        <a href="{{ backpack_url('login') }}">School portal</a>
    </footer>
</body>
</html>
