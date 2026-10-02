<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Admissions Overview | Springcare Academy</title>
    <meta name="description" content="Explore the steps for beginning your childcare journey with Springcare Academy.">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main class="admissions-overview" id="top">
        <section class="admissions-overview-hero" aria-labelledby="admissions-title">
            <div class="admissions-overview-hero-inner">
                <p class="admissions-overview-eyebrow">Admissions</p>
                <h1 id="admissions-title">A caring start for your child</h1>
                <p class="admissions-overview-lede">Choosing childcare is a meaningful step. Start by exploring Springcare Academy's approach, then gather the details that will help your family decide what feels right.</p>
            </div>
        </section>

        <section class="admissions-overview-content" aria-labelledby="steps-title">
            <div class="admissions-overview-note">
                <p>Availability, age groups, schedules, and enrollment requirements can change. Confirm current details directly with Springcare Academy before making plans.</p>
            </div>

            <h2 id="steps-title">Your first steps</h2>
            <ol class="admissions-overview-steps">
                <li>
                    <strong>Get to know our care</strong>
                    Explore how play, familiar routines, and caring relationships support children through the day.
                </li>
                <li>
                    <strong>Think about your child's needs</strong>
                    Consider the routines, interests, schedule, and support that will help your child feel comfortable.
                </li>
                <li>
                    <strong>Prepare your questions</strong>
                    Ask about availability, daily routines, meals, rest, communication with families, and settling in.
                </li>
                <li>
                    <strong>Review the practical details</strong>
                    Confirm current tuition, required documents, schedules, and what your child should bring.
                </li>
                <li>
                    <strong>Confirm how to apply</strong>
                    Contact the academy for the current application steps and any forms your family will need.
                </li>
            </ol>

            <div class="admissions-overview-actions">
                <a class="button button-primary" href="{{ route('admissions.fees') }}">View fees &amp; tuition</a>
                <a class="button button-secondary" href="{{ url('/#approach') }}">Explore our care</a>
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
