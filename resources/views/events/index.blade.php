<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Events at Springcare | Springcare Academy</title>
    <meta name="description" content="Upcoming events and highlights from Springcare Academy.">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main>
        <section class="events events-archive" aria-labelledby="events-title">
            <div class="events-heading">
                <p class="eyebrow">Gather, learn, and celebrate</p>
                <h1 id="events-title">Events at Springcare</h1>
                <p>Keep up with what's coming up and look back at moments we've shared.</p>
                <a class="events-back-link" href="{{ url('/#events') }}">Back to Springcare Academy</a>
            </div>

            <div class="events-groups">
                <section class="events-group" aria-labelledby="upcoming-events-title">
                    <div class="events-group-heading">
                        <h2 id="upcoming-events-title">Upcoming events</h2>
                        <span>Coming up</span>
                    </div>
                    @forelse ($upcomingEvents as $event)
                        <article class="event-item">
                            @if ($event->images)
                                <div class="event-images" role="group" aria-label="Photos from {{ $event->title }}">
                                    @foreach ($event->images as $image)
                                        <img src="{{ asset('storage/' . $image) }}" alt="{{ $event->title }} photo {{ $loop->iteration }}" loading="lazy">
                                    @endforeach
                                </div>
                            @endif
                            <div class="event-item-details">
                                <time class="event-date" datetime="{{ $event->starts_at->toDateString() }}">
                                    <span>{{ $event->starts_at->format('M') }}</span>
                                    <strong>{{ $event->starts_at->format('j') }}</strong>
                                    <small>{{ $event->starts_at->format('Y') }}</small>
                                </time>
                                <div class="event-item-copy">
                                    <h3>{{ $event->title }}</h3>
                                    <p class="event-item-meta">
                                        <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->format('l, F j, Y') }} at {{ $event->starts_at->format('g:i A') }}</time>
                                        @if ($event->ends_at)
                                            <span> to {{ $event->ends_at->format('g:i A') }}</span>
                                        @endif
                                        @if ($event->location)
                                            <span class="event-item-location">{{ $event->location }}</span>
                                        @endif
                                    </p>
                                    @if ($event->description)
                                        <p class="event-item-description">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="events-empty">Details of upcoming events will be shared here soon.</p>
                    @endforelse
                </section>

                <section class="events-group" aria-labelledby="past-events-title">
                    <div class="events-group-heading">
                        <h2 id="past-events-title">Past events</h2>
                        <span>From our archive</span>
                    </div>
                    @forelse ($pastEvents as $event)
                        <article class="event-item">
                            @if ($event->images)
                                <div class="event-images" role="group" aria-label="Photos from {{ $event->title }}">
                                    @foreach ($event->images as $image)
                                        <img src="{{ asset('storage/' . $image) }}" alt="{{ $event->title }} photo {{ $loop->iteration }}" loading="lazy">
                                    @endforeach
                                </div>
                            @endif
                            <div class="event-item-details">
                                <time class="event-date" datetime="{{ $event->starts_at->toDateString() }}">
                                    <span>{{ $event->starts_at->format('M') }}</span>
                                    <strong>{{ $event->starts_at->format('j') }}</strong>
                                    <small>{{ $event->starts_at->format('Y') }}</small>
                                </time>
                                <div class="event-item-copy">
                                    <h3>{{ $event->title }}</h3>
                                    <p class="event-item-meta">
                                        <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->format('l, F j, Y') }} at {{ $event->starts_at->format('g:i A') }}</time>
                                        @if ($event->ends_at)
                                            <span> to {{ $event->ends_at->format('g:i A') }}</span>
                                        @endif
                                        @if ($event->location)
                                            <span class="event-item-location">{{ $event->location }}</span>
                                        @endif
                                    </p>
                                    @if ($event->description)
                                        <p class="event-item-description">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="events-empty">Past event highlights will appear here.</p>
                    @endforelse
                </section>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>Springcare Academy</p>
        <p>&copy; {{ date('Y') }} Springcare Academy</p>
        <a href="{{ backpack_url('login') }}">Staff portal</a>
    </footer>
</body>
</html>
