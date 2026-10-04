@basset('https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js')
@basset('https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js')
@basset('https://cdn.jsdelivr.net/npm/noty@3.2.0-beta-deprecated/lib/noty.min.js')
@basset('https://cdn.jsdelivr.net/npm/sweetalert@2.1.2/dist/sweetalert.min.js')

@if (backpack_theme_config('scripts') && count(backpack_theme_config('scripts')))
    @foreach (backpack_theme_config('scripts') as $path)
        @if(is_array($path))
            @basset(...$path)
        @else
            @basset($path)
        @endif
    @endforeach
@endif

@if (backpack_theme_config('mix_scripts') && count(backpack_theme_config('mix_scripts')))
    @foreach (backpack_theme_config('mix_scripts') as $path => $manifest)
        <script type="text/javascript" src="{{ mix($path, $manifest) }}"></script>
    @endforeach
@endif

@if (backpack_theme_config('vite_scripts') && count(backpack_theme_config('vite_scripts')))
    @vite(backpack_theme_config('vite_scripts'))
@endif

@include(backpack_view('inc.alerts'))

@if(config('app.debug'))
    @include('crud::inc.ajax_error_frame')
@endif

@push('after_scripts')
    @basset(base_path('vendor/backpack/crud/src/resources/assets/js/common.js'))
@endpush

@bassetBlock('academy/form-confirmations.js')
<script>
    (() => {
        const pendingForms = new WeakSet();
        const confirmedForms = new WeakSet();

        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
            if (confirmedForms.has(form)) {
                confirmedForms.delete(form);
                return;
            }

            event.preventDefault();
            if (pendingForms.has(form) || !form.reportValidity()) return;
            pendingForms.add(form);
            const submitter = event.submitter;
            const danger = form.dataset.confirmDanger === 'true';

            swal({
                title: form.dataset.confirmTitle || 'Confirm Action',
                text: form.dataset.confirm,
                icon: danger ? 'warning' : 'info',
                buttons: {
                    cancel: { text: 'Cancel', value: null, visible: true },
                    confirm: { text: form.dataset.confirmButton || 'Continue', value: true, visible: true },
                },
                dangerMode: danger,
            }).then(confirmed => {
                if (!confirmed || !form.isConnected) return;
                confirmedForms.add(form);
                HTMLFormElement.prototype.requestSubmit.call(form, submitter?.form === form ? submitter : undefined);
            }).catch(error => {
                console.error('Unable to complete the confirmation.', error);
            }).finally(() => {
                pendingForms.delete(form);
                confirmedForms.delete(form);
            });
        });
    })();
</script>
@endBassetBlock
