<header class="site-header">
    <a class="wordmark" href="{{ url('/#top') }}" aria-label="Springcare Academy home">
        <img class="wordmark-mark" src="{{ asset('images/logo.png') }}" alt="">
        <span class="wordmark-copy">
            <span class="wordmark-name">SPRINGCARE</span>
            <span class="wordmark-type">ACADEMY</span>
        </span>
    </a>
    <nav class="main-nav" aria-label="Main navigation">
        <a href="{{ url('/#approach') }}">Our care</a>
        <a href="{{ url('/#early-years') }}">Early years</a>
        <details class="site-nav-dropdown">
            <summary>Admissions</summary>
            <div class="site-nav-dropdown-menu">
                <a href="{{ route('admissions.overview') }}">Admissions Overview</a>
                <a href="#how-to-apply">How to Apply</a>
                <a href="{{ route('admissions.fees') }}">Fees &amp; Tuition</a>
                <a href="#what-to-bring">What to Bring</a>
                <a href="#book-a-visit">Book a Visit</a>
                <a href="#apply-now">Apply Now</a>
            </div>
        </details>
        <details class="site-nav-dropdown">
            <summary>Teams</summary>
            <div class="site-nav-dropdown-menu">
                <a href="{{ route('management.index') }}">Management</a>
                <a href="{{ url('/#staffs') }}">Staffs</a>
            </div>
        </details>
        <a href="{{ route('jobs.index') }}">Jobs</a>
        <a class="portal-link" href="{{ backpack_url('login') }}">School portal</a>
    </nav>
    <details class="mobile-menu">
        <summary aria-label="Open navigation">
            <span class="mobile-menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
        </summary>
        <nav class="mobile-menu-panel" aria-label="Mobile navigation">
            <a href="{{ url('/#approach') }}">Our care</a>
            <a href="{{ url('/#early-years') }}">Early years</a>
            <a href="{{ route('admissions.overview') }}">Admissions Overview</a>
            <a href="#how-to-apply">How to Apply</a>
            <a href="{{ route('admissions.fees') }}">Fees &amp; Tuition</a>
            <a href="#what-to-bring">What to Bring</a>
            <a href="#book-a-visit">Book a Visit</a>
            <a href="#apply-now">Apply Now</a>
            <a href="{{ route('management.index') }}">Management</a>
            <a href="{{ url('/#staffs') }}">Staffs</a>
            <a href="{{ route('jobs.index') }}">Jobs</a>
            <a class="mobile-menu-portal" href="{{ backpack_url('login') }}">School portal</a>
        </nav>
    </details>
</header>

<script>
    const navigationDetails = [...document.querySelectorAll('.site-header details')];

    navigationDetails.forEach((detail) => {
        detail.addEventListener('toggle', () => {
            if (detail.open) {
                navigationDetails
                    .filter((otherDetail) => otherDetail !== detail)
                    .forEach((otherDetail) => {
                        otherDetail.open = false;
                    });
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!navigationDetails.some((detail) => detail.contains(event.target))) {
            navigationDetails.forEach((detail) => {
                detail.open = false;
            });
        }
    });

    document.querySelectorAll('.site-header a').forEach((link) => {
        link.addEventListener('click', () => {
            navigationDetails.forEach((detail) => {
                detail.open = false;
            });
        });
    });
</script>
