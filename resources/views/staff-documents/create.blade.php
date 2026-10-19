@extends('payroll.layout')

@section('payroll_content')
    <div class="mb-4">
        <a class="text-decoration-none" href="{{ route('staff-documents.index') }}"><i class="la la-arrow-left" aria-hidden="true"></i> Staff Documents</a>
        <h2 class="mt-3 mb-1">{{ $document->exists ? 'Edit draft' : 'Create a document' }}</h2>
        <p class="text-muted">Save a draft, preview the PDF, then publish it to notify the recipient.</p>
    </div>

    <form method="post" action="{{ $document->exists ? route('staff-documents.update', $document) : route('staff-documents.store') }}" class="card card-body">
        @csrf
        @if ($document->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="person_id">Recipient</label>
                <select class="form-select" id="person_id" name="person_id" required>
                    <option value="">Choose an account</option>
                    @foreach ($people as $person)
                        <option value="{{ $person->id }}" @selected(old('person_id', $document->person_id) == $person->id)>{{ $person->full_name }} | {{ $person->sacRole?->name }} | {{ $person->user?->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="document_type">Document type</label>
                <select class="form-select" id="document_type" name="document_type" required>
                    @foreach (['contract' => 'Contract', 'employment' => 'Employment letter', 'query' => 'Query letter', 'other' => 'Other'] as $type => $label)
                        <option value="{{ $type }}" @selected(old('document_type', $document->document_type ?: 'contract') === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="title">Document title</label>
                <input class="form-control" id="title" name="title" value="{{ old('title', $document->title) }}" maxlength="255" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="body">Document text</label>
                <textarea class="form-control" id="body" name="body" rows="16" maxlength="50000" required>{{ old('body', $document->body) }}</textarea>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="requires_staff_signature" value="0">
                    <input class="form-check-input" id="requires_staff_signature" name="requires_staff_signature" type="checkbox" value="1" @checked(old('requires_staff_signature', $document->requires_staff_signature))>
                    <label class="form-check-label" for="requires_staff_signature">Include staff signature and date lines</label>
                </div>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button class="btn btn-primary" type="submit"><i class="la la-save" aria-hidden="true"></i> Save draft</button>
                <a class="btn btn-outline-secondary" href="{{ route('staff-documents.index') }}">Cancel</a>
            </div>
        </div>
    </form>
@endsection
