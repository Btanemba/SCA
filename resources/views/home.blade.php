<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Springcare Academy</title>
    <meta name="description" content="Springcare Academy offers a caring daycare and early learning experience where children grow through play, connection, and discovery.">
    @vite('resources/css/home.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <aside class="resumption-notice" aria-label="School resumption notice">
        <div class="resumption-notice-track">
            <span class="resumption-notice-label">Notice</span>
            <span>Springcare Academy Starts Operations in September 2027.</span>
        </div>
    </aside>

    <main id="top">
        <section class="hero" aria-labelledby="hero-title">
            <img
                class="hero-image"
                src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=2200&q=85"
                alt="Young children exploring and playing together"
                fetchpriority="high"
            >
            <div class="hero-shade" aria-hidden="true"></div>
            <div class="hero-content">
                <p class="eyebrow">A caring start for little learners</p>
                <h1 id="hero-title">Springcare Academy</h1>
                <p class="hero-copy">Daycare and early learning where little ones build confidence through care, play, and discovery.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="#approach">Explore our care</a>
                    <a class="button button-secondary" href="#day-at-springcare">A day at Springcare</a>
                </div>
            </div>
        </section>

        <div class="values-strip" aria-label="Our values">
            <span>Care that feels personal</span>
            <span>Learning through play</span>
            <span>Room to grow</span>
            <span>Families as partners</span>
        </div>

        <section class="intro" id="approach">
            <div>
                <p class="eyebrow">Our approach</p>
                <h2>A place to play, learn and grow.</h2>
            </div>
            <div class="intro-copy">
                <p>Every child develops in their own way. We give children a safe, caring environment where they can be themselves, explore the world around them, and take those little steps towards becoming more independent.</p>
                <div class="principles">
                    <article class="principle">
                        <span class="principle-number">01</span>
                        <h3>Feel safe</h3>
                        <p>Warm relationships, familiar faces, and gentle routines help children feel secure and confident.</p>
                    </article>
                    <article class="principle">
                        <span class="principle-number">02</span>
                        <h3>Learn through play</h3>
                        <p>Children are naturally curious. We encourage them to explore, make things, ask questions, and discover something new every day.</p>
                    </article>
                    <article class="principle">
                        <span class="principle-number">03</span>
                        <h3>Grow with confidence</h3>
                        <p>We celebrate each child's progress, encourage their independence, and work together with families to support their journey.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="early-years" id="early-years" aria-labelledby="early-years-title">
            <h2 id="early-years-title">Enrolling at Springcare Academy</h2>
            <div class="early-years-layout">
                <img
                    class="early-years-image"
                    src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1400&q=85"
                    alt="Young children sharing a playful moment outdoors"
                    loading="lazy"
                >
                <div class="early-years-copy">
                    <p>For little ones, a day away from home is full of new faces, feelings, and discoveries. Warm, familiar care helps each child settle in and feel at ease.</p>
                    <p>Play gives children room to explore at their own pace: to make, imagine, move, listen, and try again. Small moments of curiosity can become the start of something wonderful.</p>
                    <p>And when families and caregivers share encouragement and understanding, children have a stronger sense of belonging as they grow.</p>
                    <p class="early-years-signoff"><span>Their first steps.</span><strong>Their own pace.</strong><span>A bright beginning.</span></p>
                </div>
            </div>
        </section>

        <section class="care-reasons" aria-labelledby="care-reasons-title">
            <div class="care-reasons-heading">
                <p class="eyebrow">The little things matter</p>
                <h2 id="care-reasons-title">What makes a day feel like a good beginning?</h2>
                <p>Early care is more than keeping busy. It is a day with room to feel safe, follow curiosity, and grow at a child's own pace.</p>
            </div>

            <div class="care-reasons-layout">
                <ol class="care-reasons-list">
                    <li>
                        <span class="care-reason-number">1</span>
                        <div>
                            <h3>Feeling safe and known</h3>
                            <p>Familiar care and warm connection help children settle in and feel they belong.</p>
                        </div>
                    </li>
                    <li>
                        <span class="care-reason-number">2</span>
                        <div>
                            <h3>Learning through play</h3>
                            <p>Play gives little learners space to imagine, experiment, and discover new things.</p>
                        </div>
                    </li>
                    <li>
                        <span class="care-reason-number">3</span>
                        <div>
                            <h3>Growing independence</h3>
                            <p>Everyday routines let children practice small steps and celebrate what they can do.</p>
                        </div>
                    </li>
                    <li>
                        <span class="care-reason-number">4</span>
                        <div>
                            <h3>Time to move and rest</h3>
                            <p>A thoughtful day makes room for active moments as well as quieter ones.</p>
                        </div>
                    </li>
                    <li>
                        <span class="care-reason-number">5</span>
                        <div>
                            <h3>Growing together with family</h3>
                            <p>When families and caregivers share what matters, children feel supported across their day.</p>
                        </div>
                    </li>
                </ol>

                <figure class="care-reasons-image-wrap">
                    <img
                        class="care-reasons-image"
                        src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1200&q=85"
                        alt="Children learning together in a bright classroom"
                        loading="lazy"
                    >
                    <figcaption>Little moments become big discoveries.</figcaption>
                </figure>
            </div>
        </section>

        <section class="school-life" id="day-at-springcare">
            <img
                class="school-life-image"
                src="https://images.unsplash.com/photo-1472162072942-cd5147eb3902?auto=format&fit=crop&w=1400&q=85"
                alt="Children spending time together during a playful activity"
                loading="lazy"
            >
            <div class="school-life-copy">
                <p class="eyebrow">The daycare days</p>
                <h2>A day for play, care, and discovery.</h2>
                <p>Stories, conversations, creative play, movement, and moments to rest all help little ones make sense of their world. Every day is a chance to feel connected and discover something new.</p>
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