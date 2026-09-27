@php
    $securityToday = now()->format('F j, Y');
@endphp

<style>
    .security-console { --console-ink: #18324b; --console-muted: #718096; --console-line: #dce7ed; --console-mint: #dff4eb; --console-blue: #2779a7; color: var(--console-ink); }
    .security-console .console-hero { background: linear-gradient(120deg, #eaf8f0 0%, #f7fbfa 52%, #e8f4f8 100%); border: 1px solid #d8e9e7; border-radius: 18px; padding: 28px; }
    .security-console .eyebrow { color: #23856e; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .security-console .console-title { font-size: clamp(1.7rem, 3vw, 2.55rem); font-weight: 750; letter-spacing: 0; }
    .security-console .date-chip { background: #fff; border: 1px solid #cfe3dc; border-radius: 999px; color: #27735e; font-weight: 700; padding: 9px 15px; }
    .security-console .search-shell { background: #fff; border: 1px solid var(--console-line); border-radius: 14px; box-shadow: 0 12px 30px rgba(44, 76, 95, .08); padding: 18px; }
    .security-console .search-input { border: 0; box-shadow: none; font-size: 1.05rem; min-height: 48px; }
    .security-console .search-input:focus { box-shadow: none; }
    .security-console .result-card { align-items: center; background: #fff; border: 1px solid var(--console-line); border-radius: 13px; display: flex; gap: 14px; padding: 12px 15px; text-align: left; transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease; width: 100%; }
    .security-console .result-card:hover { border-color: #74bea9; box-shadow: 0 8px 20px rgba(44, 76, 95, .09); transform: translateY(-1px); }
    .security-console .photo { align-items: center; background: #dcefe9; border-radius: 12px; color: #27735e; display: inline-flex; flex: 0 0 auto; font-weight: 800; height: 68px; justify-content: center; object-fit: cover; overflow: hidden; width: 68px; }
    .security-console .photo-lg { border-radius: 18px; height: 132px; width: 132px; }
    .security-console .photo-sm { border-radius: 14px; height: 78px; width: 78px; }
    .security-console .state-pill { border-radius: 999px; font-size: .72rem; font-weight: 750; padding: 6px 10px; white-space: nowrap; }
    .security-console .state-new { background: #e7f1fb; color: #28628e; }
    .security-console .state-present { background: #e1f5e9; color: #26744d; }
    .security-console .state-done { background: #edf0f3; color: #637181; }
    .security-console .detail-card { background: #fff; border: 1px solid var(--console-line); border-radius: 16px; box-shadow: 0 12px 30px rgba(44, 76, 95, .07); }
    .security-console .detail-header { border-bottom: 1px solid #e8eff1; padding: 18px 22px; }
    .security-console .detail-body { padding: 22px; }
    .security-console .readonly-field { background: #f4f8f8; border-color: #e2ecec; }
    .security-console .contact-preview { align-items: center; background: #f5faf8; border: 1px solid #dcece7; border-radius: 14px; display: flex; gap: 14px; margin-top: 12px; min-height: 100px; padding: 10px; }
    .security-console .action-button { border-radius: 10px; font-weight: 750; min-height: 46px; padding: 10px 20px; }
    @media (max-width: 575px) { .security-console .console-hero { padding: 22px 18px; } .security-console .detail-body { padding: 17px; } }
</style>

<div class="container-fluid px-0 security-console">
    <div class="console-hero mb-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="eyebrow mb-2">SpringCare Academy · front desk</div>
                <h1 class="console-title mb-2">SpringCare Academy Check-in</h1>
                <p class="text-muted mb-0">Find a child, verify the authorized person, and record today’s arrival or pickup.</p>
            </div>
            <span class="date-chip">{{ $securityToday }}</span>
        </div>
    </div>

    <div id="attendance-alert" class="alert d-none" role="alert"></div>

    <div class="search-shell mb-3">
        <label for="child-search" class="form-label fw-bold mb-1">Find a child</label>
        <div class="input-group input-group-lg">
            <span class="input-group-text bg-white border-0 ps-1"><i class="la la-search text-muted"></i></span>
            <input id="child-search" type="search" class="form-control search-input" placeholder="Search by name or student ID" autocomplete="off">
        </div>
        <div id="search-status" class="small text-muted mt-1">Enter at least 2 characters to search.</div>
        <div id="search-results" class="d-grid gap-2 mt-3"></div>
    </div>

    <section id="attendance-panel" class="detail-card d-none" aria-live="polite"></section>
</div>

@push('after_scripts')
<script>
(() => {
    function initializeAttendanceConsole() {
    const searchInput = document.getElementById('child-search');
    const searchStatus = document.getElementById('search-status');
    const searchResults = document.getElementById('search-results');
    const attendancePanel = document.getElementById('attendance-panel');
    const alertBox = document.getElementById('attendance-alert');
    const searchUrl = @json(route('security.attendance.search'));
    const dropOffUrl = @json(route('security.attendance.drop-off'));
    const pickUpUrl = @json(route('security.attendance.pick-up'));
    const csrfToken = @json(csrf_token());
    let searchTimer;
    let searchSequence = 0;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
    }

    function initials(name) {
        return String(name || '?').split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
    }

    function photo(person, className = 'photo') {
        return person?.image_url
            ? `<img class="${className}" src="${escapeHtml(person.image_url)}" alt="Photo of ${escapeHtml(person.name)}">`
            : `<span class="${className}" aria-label="No photo">${escapeHtml(initials(person?.name))}</span>`;
    }

    function showAlert(message, type = 'success') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function contactOptions(contacts, permission) {
        const allowed = contacts.filter((contact) => contact[permission]);
        if (!allowed.length) return '<option value="">No authorized person is registered</option>';
        return '<option value="">Select person</option>' + allowed.map((contact) => `<option value="${contact.id}">${escapeHtml(contact.name)}${contact.relationship ? ` · ${escapeHtml(contact.relationship)}` : ''}</option>`).join('');
    }

    function bindContactPreview(form, contacts, permission) {
        const select = form.querySelector('select[name="pickup_contact_id"]');
        const preview = form.querySelector('[data-contact-preview]');
        const update = () => {
            const contact = contacts.find((item) => String(item.id) === String(select.value));
            preview.innerHTML = contact
                ? `${photo(contact, 'photo-sm')}<div><strong>${escapeHtml(contact.name)}</strong><div class="small text-muted">${escapeHtml(contact.relationship || 'Authorized contact')}</div></div>`
                : '<span class="small text-muted">Select the person to verify their photo.</span>';
        };
        select.addEventListener('change', update);
        update();
    }

    function childHeader(child, label, tone) {
        const age = child.age === null || child.age === undefined ? 'Age not provided' : `${child.age} ${child.age === 1 ? 'year' : 'years'} old`;
        return `<div class="detail-header d-flex flex-wrap align-items-center justify-content-between gap-3"><div class="d-flex align-items-center gap-3">${photo(child, 'photo-lg')}<div><div class="small text-muted">${escapeHtml(child.student_id)} · ${escapeHtml(age)}</div><h2 class="h4 mb-1">${escapeHtml(child.name)}</h2><span class="state-pill ${tone}">${label}</span></div></div></div>`;
    }

    function readonlyField(label, value) {
        return `<div><label class="form-label small text-muted mb-1">${label}</label><input class="form-control readonly-field" value="${escapeHtml(value)}" readonly></div>`;
    }

    function renderPanel(child) {
        const attendance = child.attendance;
        attendancePanel.classList.remove('d-none');
        if (!attendance) {
            attendancePanel.innerHTML = `${childHeader(child, 'Ready for drop-off', 'state-new')}<div class="detail-body"><form data-action="drop-off"><input type="hidden" name="child_id" value="${child.id}"><div class="row g-4 align-items-end"><div class="col-lg-7"><label class="form-label fw-bold" for="drop-off-contact">Dropped off by</label><select id="drop-off-contact" name="pickup_contact_id" class="form-select form-select-lg" required>${contactOptions(child.contacts, 'can_drop_off')}</select><div data-contact-preview class="contact-preview"></div></div><div class="col-lg-5"><button class="btn btn-primary action-button w-100" type="submit"><i class="la la-sign-in-alt me-1"></i> Record drop-off</button></div></div></form></div>`;
            bindContactPreview(attendancePanel.querySelector('form'), child.contacts, 'can_drop_off');
        } else if (attendance.is_present) {
            attendancePanel.innerHTML = `${childHeader(child, 'Currently present', 'state-present')}<div class="detail-body"><div class="row g-3 mb-4">${readonlyField('Dropped off at', attendance.dropped_off_at)}${readonlyField('Dropped off by', attendance.dropped_off_by_name)}</div><form data-action="pick-up"><input type="hidden" name="child_id" value="${child.id}"><div class="row g-4 align-items-end"><div class="col-lg-7"><label class="form-label fw-bold" for="pick-up-contact">Picked up by</label><select id="pick-up-contact" name="pickup_contact_id" class="form-select form-select-lg" required>${contactOptions(child.contacts, 'can_pick_up')}</select><div data-contact-preview class="contact-preview"></div></div><div class="col-lg-5"><button class="btn btn-success action-button w-100" type="submit"><i class="la la-sign-out-alt me-1"></i> Record pickup</button></div></div></form></div>`;
            bindContactPreview(attendancePanel.querySelector('form'), child.contacts, 'can_pick_up');
        } else {
            attendancePanel.innerHTML = `${childHeader(child, 'Attendance complete', 'state-done')}<div class="detail-body"><div class="row g-3">${readonlyField('Dropped off at', attendance.dropped_off_at)}${readonlyField('Dropped off by', attendance.dropped_off_by_name)}${readonlyField('Picked up at', attendance.picked_up_at)}${readonlyField('Picked up by', attendance.picked_up_by_name)}</div><div class="alert alert-light border mt-4 mb-0">This child has already been picked up today.</div></div>`;
        }
        attendancePanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function searchChildren(term) {
        const sequence = ++searchSequence;
        searchStatus.textContent = 'Searching...';
        const response = await fetch(`${searchUrl}?term=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
        if (sequence !== searchSequence) return;
        if (!response.ok) throw new Error('Unable to search children.');
        const children = await response.json();
        searchResults.innerHTML = '';
        searchStatus.textContent = children.length ? 'Select a child to continue.' : 'No children found.';
        children.forEach((child) => {
            const attendance = child.attendance;
            const state = attendance?.is_present ? ['Present', 'state-present'] : attendance ? ['Completed', 'state-done'] : ['Not checked in', 'state-new'];
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'result-card';
            const age = child.age === null || child.age === undefined ? 'Age not provided' : `${child.age} ${child.age === 1 ? 'year' : 'years'} old`;
            button.innerHTML = `${photo(child)}<span class="flex-grow-1"><strong class="d-block">${escapeHtml(child.name)}</strong><small class="text-muted">${escapeHtml(child.student_id)} · ${escapeHtml(age)}</small></span><span class="state-pill ${state[1]}">${state[0]}</span><i class="la la-angle-right text-muted"></i>`;
            button.addEventListener('click', () => renderPanel(child));
            searchResults.appendChild(button);
        });
    }

    if (!searchInput || !searchStatus || !searchResults || !attendancePanel || !alertBox) {
        return;
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        attendancePanel.classList.add('d-none');
        const term = searchInput.value.trim();
        if (term.length < 2) { searchResults.innerHTML = ''; searchStatus.textContent = 'Enter at least 2 characters to search.'; return; }
        searchTimer = setTimeout(() => searchChildren(term).catch((error) => showAlert(error.message, 'danger')), 250);
    });

    attendancePanel.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch(form.dataset.action === 'drop-off' ? dropOffUrl : pickUpUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(Object.fromEntries(new FormData(form))) });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Unable to record attendance.');
            showAlert(result.message);
            attendancePanel.classList.add('d-none');
            searchInput.dispatchEvent(new Event('input'));
        } catch (error) { showAlert(error.message, 'danger'); button.disabled = false; }
    });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAttendanceConsole, { once: true });
    } else {
        initializeAttendanceConsole();
    }
})();
</script>
@endpush
