@extends('payroll.layout')

@section('payroll_content')
    <h2 class="mb-4">My Documents</h2>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Document</th><th>Type</th><th>Published</th><th class="text-end">Download</th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td>{{ $document->title }}</td>
                        <td>{{ $document->type_label }}</td>
                        <td>{{ $document->published_at->format('M j, Y') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('staff-documents.download', $document) }}" aria-label="Download {{ $document->title }}" title="Download"><i class="la la-download" aria-hidden="true"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No documents have been published to your account.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $documents->links() }}
@endsection
