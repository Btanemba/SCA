<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Management | Springcare Academy</title>
    <meta name="description" content="Meet the board and management team guiding Springcare Academy.">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main class="management-page" id="top">
        <section class="management-hero" aria-labelledby="management-title">
            <div class="management-hero-inner">
                <p class="management-eyebrow">Leadership and governance</p>
                <h1 id="management-title">Meet our management.</h1>
                <p class="management-hero-copy">The people guiding Springcare Academy's vision, values, and commitment to every child's beginning.</p>
            </div>
        </section>

        <section class="management-content" aria-labelledby="management-team-title">
            <div class="management-content-inner">
                <div class="management-section-heading">
                    <p class="management-eyebrow">Springcare Academy</p>
                    <h2 id="management-team-title">Board and management team</h2>
                </div>

                @if ($managementMembers->isEmpty())
                    <p class="management-empty">Management profiles will be published here soon.</p>
                @else
                    @include('management.partials.member-card', [
                        'member' => $managementMembers->first(),
                        'featured' => true,
                    ])

                    @if ($managementMembers->count() > 1)
                        <ul class="management-grid">
                            @foreach ($managementMembers->skip(1) as $member)
                                <li>
                                    @include('management.partials.member-card', [
                                        'member' => $member,
                                        'featured' => false,
                                    ])
                                </li>
                            @endforeach
                        </ul>
                    @endif
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
