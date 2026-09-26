<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Fees &amp; Tuition | Springcare Academy</title>
    <meta name="description" content="Learn what to discuss with Springcare Academy when planning childcare tuition and fees.">
    @vite('resources/css/home.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main class="fees-page" id="top">
        <section class="fees-hero" aria-labelledby="fees-title">
            <div class="fees-hero-inner">
                <p class="fees-eyebrow">Admissions</p>
                <h1 id="fees-title">Fees &amp; Tuition</h1>
                <p class="fees-lede">Clear information helps families plan with confidence. Contact Springcare Academy for current tuition details and a fee breakdown suited to your child's care needs.</p>
            </div>
        </section>

        <section class="fees-content" aria-labelledby="planning-title">
            <div class="fees-note">
                <p>Tuition amounts and payment arrangements are not listed here because current rates have not been provided. Please confirm all costs directly with the academy before enrollment.</p>
            </div>

            <h2 id="planning-title">What to confirm with our team</h2>
            <ul class="fees-checklist">
                <li>
                    <strong>Tuition for your care schedule</strong>
                    Ask how the fee is calculated for the days and hours your family needs.
                </li>
                <li>
                    <strong>Enrollment costs</strong>
                    Confirm whether there are registration, deposit, or other one-time fees.
                </li>
                <li>
                    <strong>Payment arrangements</strong>
                    Ask when payments are due and which payment methods are accepted.
                </li>
                <li>
                    <strong>What is included</strong>
                    Check whether meals, supplies, activities, or other items are part of tuition.
                </li>
                <li>
                    <strong>Changes and absences</strong>
                    Ask how schedule changes, absences, holidays, and closures affect fees.
                </li>
                <li>
                    <strong>Additional charges</strong>
                    Confirm whether late pickup or optional activities have separate costs.
                </li>
            </ul>

            <a class="fees-back-link" href="{{ url('/#early-years') }}">Back to admissions overview</a>
        </section>
    </main>

    <footer class="site-footer">
        <p>Springcare Academy</p>
        <p>&copy; {{ date('Y') }} Springcare Academy</p>
        <a href="{{ backpack_url('login') }}">School portal</a>
    </footer>
</body>
</html>