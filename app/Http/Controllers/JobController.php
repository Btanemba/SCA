<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobApplicationRequest;
use App\Models\JobOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(): View
    {
        $openings = JobOpening::open()
            ->orderByRaw('closes_at is null desc')
            ->orderBy('closes_at')
            ->orderBy('title')
            ->get();

        return view('jobs', compact('openings'));
    }

    public function show(JobOpening $jobOpening): View
    {
        abort_unless($jobOpening->isOpen(), 404);

        return view('jobs.show', compact('jobOpening'));
    }

    public function apply(JobApplicationRequest $request, JobOpening $jobOpening): RedirectResponse
    {
        abort_unless($jobOpening->isOpen(), 404);

        $data = $request->safe()->except('resume', 'website');
        // CVs stay on the private disk and are only reachable through the admin download route
        $data['resume_path'] = $request->file('resume')->store('job-applications/'.$jobOpening->getKey(), 'local');

        $jobOpening->applications()->create($data);

        return back()
            ->with('application_status', 'Thank you. Your application has been received.')
            ->withFragment('apply');
    }
}
