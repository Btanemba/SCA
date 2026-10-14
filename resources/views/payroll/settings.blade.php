@extends('payroll.layout')

@section('payroll_content')
    @php
        $isCeo = backpack_user()->person->sacRole->code === \App\Models\Person::ROLE_FOUNDER;
        $showDocuments = $errors->has('title') || $errors->has('document');
    @endphp
    <h2 class="mb-4">Academy Settings</h2>
    <ul class="nav nav-tabs mb-4" id="academy-settings-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showDocuments ? '' : 'active' }}" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-panel" type="button" role="tab" aria-controls="settings-panel" aria-selected="{{ $showDocuments ? 'false' : 'true' }}">Settings</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showDocuments ? 'active' : '' }}" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-panel" type="button" role="tab" aria-controls="documents-panel" aria-selected="{{ $showDocuments ? 'true' : 'false' }}">Documents</button>
        </li>
    </ul>
    <div class="tab-content" id="academy-settings-tab-content">
        <section class="tab-pane fade {{ $showDocuments ? '' : 'show active' }}" id="settings-panel" role="tabpanel" aria-labelledby="settings-tab" tabindex="0">
            <form action="{{ route('academy.settings.update') }}" method="post" enctype="multipart/form-data">
        @csrf @method('PUT')
            <h3 class="h5">Letterhead</h3>
            <div class="row">
            @foreach (['name' => 'Official academy name', 'phone' => 'Phone', 'email' => 'Email', 'website' => 'Website'] as $field => $label)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input class="form-control" id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'website' ? 'url' : 'text') }}" value="{{ old($field, $settings?->$field) }}" maxlength="255" @required($field !== 'website')>
                </div>
            @endforeach
            <div class="col-12 mb-3">
                <label class="form-label" for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" maxlength="2000" required>{{ old('address', $settings?->address) }}</textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="logo">Logo</label>
                <input class="form-control" type="file" id="logo" name="logo" accept="image/png,image/jpeg" data-preview="logo-preview">
                <img id="logo-preview" src="{{ $settings?->logo_path ? route('academy.settings.asset', 'logo').'?v='.$settings->updated_at->timestamp : '' }}" alt="Academy logo" class="mt-2" style="max-width:200px;max-height:100px" @if (! $settings?->logo_path) hidden @endif>
            </div>
            @if ($isCeo)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="ceo_name">CEO name</label>
                    <input class="form-control" id="ceo_name" name="ceo_name" value="{{ old('ceo_name', $settings?->ceo_name) }}" maxlength="255" required>
                    <label class="form-label mt-3" for="signature">CEO signature</label>
                    <input class="form-control" type="file" id="signature" name="signature" accept="image/png,image/jpeg" data-preview="signature-preview">
                    <img id="signature-preview" src="{{ $settings?->signature_path ? route('academy.settings.asset', 'signature').'?v='.$settings->updated_at->timestamp : '' }}" alt="CEO signature" class="mt-2" style="max-width:200px;max-height:80px" @if (! $settings?->signature_path) hidden @endif>
                </div>
            @endif
            </div>
            <hr>
            <h3 class="h5">Bank Payment Instructions</h3>
            <div class="row">
            @foreach (['bank_name' => 'Bank name', 'bank_email' => 'Bank email', 'debit_account_name' => 'Academy account name', 'debit_account_number' => 'Academy account number'] as $field => $label)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input class="form-control" id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'bank_email' ? 'email' : 'text' }}" value="{{ old($field, $settings?->$field) }}" @if ($field === 'debit_account_number') inputmode="numeric" pattern="[0-9]{10}" maxlength="10" @else maxlength="255" @endif>
                </div>
            @endforeach
            <div class="col-12 mb-3">
                <label class="form-label" for="bank_address">Bank address</label>
                <textarea class="form-control" id="bank_address" name="bank_address" rows="3" maxlength="2000">{{ old('bank_address', $settings?->bank_address) }}</textarea>
            </div>
                </div>
                <button class="btn btn-primary" type="submit"><i class="la la-save" aria-hidden="true"></i> Save Settings</button>
            </form>
        </section>
        <section class="tab-pane fade {{ $showDocuments ? 'show active' : '' }}" id="documents-panel" role="tabpanel" aria-labelledby="documents-tab" tabindex="0">
            <h3 class="h5">School Documents</h3>
            <form action="{{ route('academy.settings.documents.store') }}" method="post" enctype="multipart/form-data" class="row g-3 align-items-start mb-4">
                @csrf
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="document-title">Title</label>
                    <input class="form-control" id="document-title" name="title" value="{{ old('title') }}" maxlength="255" required>
                </div>
                <div class="col-12 col-lg-5">
                    <label class="form-label" for="school-document">Document</label>
                    <input class="form-control" id="school-document" name="document" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" required>
                    <small class="d-block text-muted mt-2">PDF, Word, Excel, PowerPoint, or text file; up to 20 MB.</small>
                </div>
                <div class="col-12 col-lg-auto pt-lg-4">
                    <button class="btn btn-primary" type="submit"><i class="la la-upload" aria-hidden="true"></i> Upload</button>
                </div>
            </form>
            @if ($documents->isEmpty())
                <p class="text-muted">No school documents uploaded yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Title</th><th>File</th><th>Uploaded</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($documents as $document)
                                <tr>
                                    <td>{{ $document->title }}</td>
                                    <td>{{ $document->original_name }}</td>
                                    <td>{{ $document->created_at->format('M j, Y') }}</td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('academy.settings.documents.download', $document) }}" aria-label="Download {{ $document->title }}"><i class="la la-download" aria-hidden="true"></i></a>
                                        <form action="{{ route('academy.settings.documents.delete', $document) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this school document?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete {{ $document->title }}"><i class="la la-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
    <script>
        document.querySelectorAll('[data-preview]').forEach(input => {
            input.addEventListener('change', () => {
                const image = document.getElementById(input.dataset.preview);
                if (input.files[0]) {
                    if (image.dataset.objectUrl) URL.revokeObjectURL(image.dataset.objectUrl);
                    image.dataset.objectUrl = URL.createObjectURL(input.files[0]);
                    image.src = image.dataset.objectUrl;
                    image.hidden = false;
                }
            });
        });
    </script>
@endsection
