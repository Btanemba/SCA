<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>Fees &amp; Tuition | Springcare Academy</title>
    <meta name="description" content="View Springcare Academy's current tuition, feeding, and admission fees.">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
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
                <p class="fees-lede">Explore Springcare Academy's current fees and charges. Contact the academy if you have questions about a fee.</p>
            </div>
        </section>

        <section class="fees-content" aria-labelledby="fees-schedule-title">
            <h2 id="fees-schedule-title">Current Fee Schedule</h2>
            <a class="fees-brochure-link" href="{{ route('admissions.fees.brochure') }}">Download Fee Brochure (PDF)</a>
            <div class="fees-table-wrap">
            <table class="fees-table" aria-label="Springcare Academy fees">
                <thead>
                    <tr>
                        <th scope="col">Fees</th>
                        <th scope="col">Age or Level</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Schedule</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fees as $fee)
                        <tr>
                            <td data-label="Fee">{{ $fee->fee_name }}</td>
                            <td data-label="Age or Level">{{ $fee->age_or_level }}</td>
                            <td data-label="Amount">{{ $fee->amount === null ? 'Coming soon' : 'N'.number_format($fee->amount) }}</td>
                            <td data-label="Schedule">{{ $fee->schedule }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Fee information is currently being updated. Please contact the academy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            <aside class="fees-payment-warning" role="note" aria-labelledby="fees-payment-warning-title">
                <h2 id="fees-payment-warning-title">Payment Warning</h2>
                <p>All payments must be made only to the school's official bank account. Cash payments are not accepted.</p>
                <p>Send proof of payment by email or to the school's WhatsApp number. Include your child's name and ID number with the proof of payment.</p>
            </aside>

            <h2 id="planning-title">What to confirm with our team</h2>
            <ul class="fees-checklist">
                <li>
                    <strong>Tuition for your care schedule</strong>
                    Ask how the fee is calculated for the days and hours your family needs.
                </li>
                <li>
                    <strong>Enrollment costs</strong>
                    Confirm the admission form, registration, and any other one-time charges listed above.
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
