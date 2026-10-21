@extends('payroll.layout')

@section('payroll_content')
    <h2 class="mb-4">{{ $account['title'] }}</h2>

    <div class="mb-3">
        <a href="{{ $account['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
            <i class="la la-external-link-alt" aria-hidden="true"></i> Open Webmail
        </a>
    </div>

    <div class="mb-3">
        <label for="info-mail-email" class="form-label">Email</label>
        <input id="info-mail-email" class="form-control" type="text" value="{{ $account['email'] ?: 'Not configured' }}" readonly>
    </div>

    <form id="info-mail-password-form">
        @csrf
        <label for="info-mail-password" class="form-label">Password</label>
        <div class="input-group">
            <input id="info-mail-password" class="form-control" type="password" value="" placeholder="Select Show to reveal" readonly autocomplete="new-password">
            <button id="info-mail-password-toggle" class="btn btn-outline-secondary" type="button"
                data-endpoint="{{ route('mail-accounts.password', $account['key']) }}" @disabled(! $passwordConfigured)>
                Show
            </button>
        </div>
        <div id="info-mail-password-status" class="form-text" role="status" aria-live="polite">
            @unless ($passwordConfigured)
                Mailbox password is not configured on the server.
            @endunless
        </div>
    </form>

    <script>
        (() => {
            const form = document.getElementById('info-mail-password-form');
            const field = document.getElementById('info-mail-password');
            const button = document.getElementById('info-mail-password-toggle');
            const status = document.getElementById('info-mail-password-status');
            let passwordLoaded = false;

            button.addEventListener('click', async () => {
                if (!passwordLoaded) {
                    button.disabled = true;
                    status.textContent = 'Loading password...';

                    try {
                        const response = await fetch(button.dataset.endpoint, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            },
                        });

                        if (!response.ok) {
                            throw new Error();
                        }

                        const payload = await response.json();
                        field.value = payload.password;
                        passwordLoaded = true;
                    } catch {
                        status.textContent = 'Could not reveal the password. Please try again.';
                        return;
                    } finally {
                        button.disabled = false;
                    }
                }

                const shouldShow = field.type === 'password';
                field.type = shouldShow ? 'text' : 'password';
                button.textContent = shouldShow ? 'Hide' : 'Show';
                button.setAttribute('aria-pressed', String(shouldShow));
                status.textContent = '';
            });
        })();
    </script>
@endsection
