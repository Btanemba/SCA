<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            // Honeypot: real applicants never fill this in
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'resume.mimes' => 'Please upload your CV as a PDF or Word document.',
            'website.prohibited' => 'Your submission could not be processed.',
        ];
    }
}
