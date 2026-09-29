@php
    $person = $field['person'] ?? null;
    $account = $person?->user;
    $email = $account?->email;
    $verified = $account?->email_verified_at;

    $status = match (true) {
        (bool) $verified => ['icon' => 'la-check-circle', 'class' => 'rif-status--ok', 'title' => 'Account active', 'detail' => 'Email verified '.$verified->diffForHumans().'. This person can sign in.'],
        (bool) $email => ['icon' => 'la-exclamation-circle', 'class' => 'rif-status--pending', 'title' => 'Registration not completed', 'detail' => 'No password set yet, so this person cannot sign in.'],
        default => ['icon' => 'la-user-slash', 'class' => 'rif-status--none', 'title' => 'No login account', 'detail' => 'Add an email address on the General tab and save first.'],
    };
@endphp

@include('crud::fields.inc.wrapper_start')
    <style>
        .rif-card { background: #fff; border: 1px solid #e3e7ea; border-radius: 12px; overflow: hidden; }
        .rif-head { align-items: center; border-bottom: 1px solid #eef1f3; display: flex; gap: 10px; padding: 14px 18px; }
        .rif-head h5 { font-size: 1rem; font-weight: 700; margin: 0; }
        .rif-body { padding: 18px; }
        .rif-status { align-items: center; border: 1px solid transparent; border-left-width: 5px; border-radius: 10px; display: flex; gap: 14px; margin-bottom: 18px; padding: 14px 18px; }
        .rif-status i { font-size: 1.9rem; line-height: 1; }
        .rif-status-title { font-size: 1.02rem; font-weight: 700; line-height: 1.3; }
        .rif-status-detail { font-size: .89rem; opacity: .85; }
        .rif-status--ok { background: #e8f6ee; border-color: #1f9254; color: #14603a; }
        .rif-status--pending { background: #fff5e3; border-color: #d99a16; color: #8a5b00; }
        .rif-status--none { background: #f1f3f5; border-color: #9aa5b1; color: #4b5563; }
        .rif-email { background: #f6f8f9; border: 1px solid #e3e7ea; border-radius: 8px; display: inline-block; font-weight: 600; padding: 3px 10px; }
        .rif-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 14px; }
        .rif-actions .btn { font-weight: 600; padding: 10px 20px; }
        #registration-invite-status { font-size: .9rem; }
    </style>

    <div class="rif-card">
        <div class="rif-head">
            <i class="la la-shield-alt text-muted" style="font-size: 1.4rem;"></i>
            <h5>Account access</h5>
        </div>

        <div class="rif-body">
            <div class="rif-status {{ $status['class'] }}" role="status">
                <i class="la {{ $status['icon'] }}"></i>
                <div>
                    <div class="rif-status-title">{{ $status['title'] }}</div>
                    <div class="rif-status-detail">{{ $status['detail'] }}</div>
                </div>
            </div>

            @if ($email)
                <p class="text-muted mb-3">
                    Send <span class="rif-email">{{ $email }}</span> a link to confirm their email address and choose a password.
                </p>
            @endif

            <div class="rif-actions">
                <button type="button"
                        class="btn {{ $verified ? 'btn-outline-primary' : 'btn-primary' }}"
                        id="send-registration-invite"
                        data-url="{{ $field['invite_url'] }}"
                        @disabled(! $email)>
                    <i class="la la-paper-plane"></i>
                    {{ $verified ? 'Resend invitation' : 'Send registration invitation' }}
                </button>

                <div class="text-muted" id="registration-invite-status" aria-live="polite">
                    @if ($person?->invited_at)
                        Last sent {{ $person->invited_at->diffForHumans() }}.
                    @endif
                </div>
            </div>
        </div>
    </div>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
(() => {
    const csrfToken = @json(csrf_token());

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('#send-registration-invite');
        if (!button) {
            return;
        }

        event.preventDefault();

        const status = document.getElementById('registration-invite-status');
        button.disabled = true;
        status.className = 'text-muted';
        status.textContent = 'Sending…';

        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const payload = await response.json().catch(() => ({}));
            status.textContent = payload.message ?? 'Something went wrong.';
            status.className = 'fw-semibold ' + (response.ok ? 'text-success' : 'text-danger');
        } catch (error) {
            status.textContent = 'Could not send the invitation. Please try again.';
            status.className = 'fw-semibold text-danger';
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
@endpush
