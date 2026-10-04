@extends('payroll.layout')

@section('payroll_content')
    @php($isCeo = backpack_user()->person->sacRole->code === \App\Models\Person::ROLE_FOUNDER)
    <h2 class="mb-4">Academy Settings</h2>
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
