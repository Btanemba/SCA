<header class="site-header">
    <a class="wordmark" href="{{ url('/#top') }}" aria-label="Springcare Academy home">
        <span class="wordmark-mark" aria-hidden="true">S</span>
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
                <a href="{{ url('/#management') }}">Management</a>
                <a href="{{ url('/#staffs') }}">Staffs</a>
            </div>
        </details>
        <a href="{{ route('jobs.index') }}">Jobs</a>
        <a class="portal-link" href="{{ backpack_url('login') }}">School portal</a>
    </nav>
</header>