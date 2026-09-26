<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f4ed">
    <title>{{ $jobOpening->title }} | Springcare Academy</title>
    <meta name="description" content="{{ $jobOpening->summary ?? 'Apply to join the team at Springcare Academy.' }}">
    @vite('resources/css/home.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body>
    @include('partials.nav')

    <main class="jobs-page" id="top">
        <section class="job-detail">
            <div class="job-detail-inner">
                <p class="jobs-eyebrow"><a href="{{ route('jobs.index') }}">&larr; All opportunities</a></p>
                <h1>{{ $jobOpening->title }}</h1>
                <p class="job-card-meta">
                    @foreach (array_filter([$jobOpening->department, $jobOpening->employment_type, $jobOpening->location, $jobOpening->salary_range]) as $detail)
                        <span>{{ $detail }}</span>
                    @endforeach
                    @if ($jobOpening->closes_at)
                        <span>Closes {{ $jobOpening->closes_at->format('j M Y') }}</span>
                    @endif
                </p>

                <div class="job-detail-body">
                    <h2>About the role</h2>
                    <p>{!! nl2br(e($jobOpening->description)) !!}</p>

                    @if ($jobOpening->requirements)
                        <h2>What we're looking for</h2>
                        <p>{!! nl2br(e($jobOpening->requirements)) !!}</p>
                    @endif
                </div>

                <div class="job-apply" id="apply">
                    <h2>Apply for this role</h2>

                    @if (session('application_status'))
                        <p class="job-apply-success">{{ session('application_status') }}</p>
                    @endif

                    @if ($errors->any())
                        <ul class="job-apply-errors">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('jobs.apply', $jobOpening) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="job-apply-grid">
                            <label>
                                First name
                                <input type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="255">
                            </label>
                            <label>
                                Last name
                                <input type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="255">
                            </label>
                            <label>
                                Email
                                <input type="email" name="email" value="{{ old('email') }}" required maxlength="255">
                            </label>
                            <label>
                                Phone
                                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="50">
                            </label>
                        </div>

                        <label class="job-apply-block">
                            CV / resume (PDF or Word, max 5 MB)
                            <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
                        </label>

                        <label class="job-apply-block">
                            Cover letter
                            <textarea name="cover_letter" rows="6" maxlength="5000">{{ old('cover_letter') }}</textarea>
                        </label>

                        <div class="job-apply-hp" aria-hidden="true">
                            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <button class="button button-primary" type="submit">Send application</button>
                    </form>
                </div>
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
