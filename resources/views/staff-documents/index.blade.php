@extends('payroll.layout')

@section('payroll_content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="mb-1">Staff Documents</h2>
            <p class="text-muted mb-0">Create private letters and publish them to individual accounts.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('staff-documents.create') }}"><i class="la la-plus" aria-hidden="true"></i> Create document</a>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter align-middle">
            <thead><tr><th>Document</th><th>Recipient</th><th>Type</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td>{{ $document->title }}</td>
                        <td>{{ $document->person?->full_name }}</td>
                        <td>{{ $document->type_label }}</td>
                        <td>{{ $document->published_at ? 'Published' : 'Draft' }}</td>
                        <td>{{ ($document->published_at ?? $document->created_at)->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('staff-documents.preview', $document) }}" target="_blank" rel="noopener" aria-label="Preview {{ $document->title }}" title="Preview"><i class="la la-eye" aria-hidden="true"></i></a>
                            @if ($document->published_at)
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('staff-documents.download', $document) }}" aria-label="Download {{ $document->title }}" title="Download"><i class="la la-download" aria-hidden="true"></i></a>
                            @else
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('staff-documents.edit', $document) }}" aria-label="Edit {{ $document->title }}" title="Edit"><i class="la la-pencil" aria-hidden="true"></i></a>
                                <form class="d-inline" method="post" action="{{ route('staff-documents.publish', $document) }}" data-confirm="Publish this document and email the recipient?" data-confirm-title="Publish this document?" data-confirm-button="Publish">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="la la-paper-plane" aria-hidden="true"></i> Publish</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No staff documents created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $documents->links() }}
@endsection
