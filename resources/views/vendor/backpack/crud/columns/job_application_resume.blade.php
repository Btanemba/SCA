@if ($entry->resume_path)
    <a href="{{ route('job-application.resume', $entry->getKey()) }}" target="_blank" rel="noopener">
        <i class="la la-file-download"></i> Download
    </a>
@else
    <span class="text-muted">&mdash;</span>
@endif
