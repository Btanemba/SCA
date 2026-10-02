<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Jobs | Springcare Academy</title>
    <meta name="description" content="Explore working with Springcare Academy and supporting young children's care, play, and learning.">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main class="jobs-page" id="top">
        <section class="jobs-hero" aria-labelledby="jobs-title">
            <div class="jobs-hero-copy">
                <p class="jobs-eyebrow">Work with purpose</p>
                <h1 id="jobs-title">Help little learners feel at home.</h1>
                <p>Working in childcare is built from meaningful everyday moments: welcoming a child, encouraging a new discovery, and helping families feel connected.</p>
                <a class="button button-primary" href="#openings">Explore opportunities</a>
            </div>
            <img
                class="jobs-hero-image"
                src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1400&q=85"
                alt="Young children learning and playing together"
                fetchpriority="high"
            >
        </section>

        <section class="jobs-values" aria-labelledby="jobs-values-title">
            <div class="jobs-values-inner">
                <p class="jobs-eyebrow">Growing together</p>
                <h2 id="jobs-values-title">The care behind every good day.</h2>
                <div class="jobs-values-grid">
                    <article class="jobs-value">
                        <h3>Care with patience</h3>
                        <p>Children need time, reassurance, and attentive adults as they find their feet in a new routine.</p>
                    </article>
                    <article class="jobs-value">
                        <h3>Play with purpose</h3>
                        <p>Small invitations to imagine, make, move, and ask questions can open up a world of learning.</p>
                    </article>
                    <article class="jobs-value">
                        <h3>Partnership with families</h3>
                        <p>Thoughtful communication helps caregivers and families support each child's sense of belonging.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="jobs-openings" id="openings" aria-labelledby="openings-title">
            <div class="jobs-openings-inner">
                <p class="jobs-eyebrow">Current opportunities</p>
                <h2 id="openings-title">Join our team.</h2>

                @forelse ($openings as $opening)
                    <article class="job-card">
                        <div class="job-card-copy">
                            <h3><a href="{{ route('jobs.show', $opening) }}">{{ $opening->title }}</a></h3>
                            <p class="job-card-meta">
                                @foreach (array_filter([$opening->department, $opening->employment_type, $opening->location]) as $detail)
                                    <span>{{ $detail }}</span>
                                @endforeach
                                @if ($opening->closes_at)
                                    <span>Closes {{ $opening->closes_at->format('j M Y') }}</span>
                                @endif
                            </p>
                            @if ($opening->summary)
                                <p class="job-card-summary">{{ $opening->summary }}</p>
                            @endif
                        </div>
                        <a class="button button-primary" href="{{ route('jobs.show', $opening) }}">View &amp; apply</a>
                    </article>
                @empty
                    <p>Role details and application instructions will be listed here when opportunities are published. Please check back for updates.</p>
                @endforelse
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
