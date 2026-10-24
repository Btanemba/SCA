<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check-in Kiosk · {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body { background: #f3f8f6; color: #18324b; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; overflow: hidden; }
        .kiosk { display: flex; flex-direction: column; height: 100%; }
        .kiosk-bar { align-items: center; background: #fff; border-bottom: 1px solid #dce7ed; display: flex; justify-content: space-between; padding: 14px 28px; }
        .kiosk-bar h1 { font-size: 1.25rem; margin: 0; }
        .kiosk-bar .clock { color: #27735e; font-size: 1.25rem; font-variant-numeric: tabular-nums; font-weight: 700; }
        .kiosk-bar a { color: #2779a7; font-size: .9rem; margin-left: 18px; text-decoration: none; }
        .kiosk-main { align-items: center; display: flex; flex: 1; justify-content: center; min-height: 0; overflow: auto; padding: 28px; }
        .idle { text-align: center; }
        .idle .pulse { animation: pulse 2s ease-in-out infinite; background: #dff4eb; border-radius: 50%; display: inline-flex; font-size: 4.5rem; height: 180px; align-items: center; justify-content: center; margin-bottom: 26px; width: 180px; }
        .idle h2 { font-size: clamp(2rem, 4vw, 3.2rem); margin: 0 0 8px; }
        .idle p { color: #718096; font-size: 1.2rem; margin: 0; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.06); } }
        .result { width: 100%; max-width: 1400px; }
        .result-layout { align-items: stretch; display: grid; gap: 22px; grid-template-columns: minmax(270px, .75fr) minmax(0, 1.5fr); }
        .contact { align-items: center; background: #fff; border: 1px solid #dce7ed; border-radius: 20px; display: flex; flex-direction: column; justify-content: center; padding: 24px; text-align: center; }
        .contact .label { color: #718096; font-size: .9rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .contact h2 { font-size: clamp(1.5rem, 2.2vw, 2rem); margin: 12px 0 0; overflow-wrap: anywhere; }
        .contact .detail { color: #718096; font-size: 1rem; margin-top: 6px; }
        .children { display: grid; gap: 22px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .child { background: #fff; border: 1px solid #dce7ed; border-radius: 20px; border-top: 10px solid #a0aec0; padding: 24px; text-align: center; }
        .child h3 { font-size: 1.7rem; margin: 16px 0 4px; }
        .child .sid { color: #718096; }
        .child .status { border-radius: 999px; display: inline-block; font-size: 1.3rem; font-weight: 800; margin-top: 16px; padding: 10px 22px; }
        .action-dropped_off { border-top-color: #28a879; } .action-dropped_off .status { background: #e1f5e9; color: #1f7a4d; }
        .action-picked_up { border-top-color: #2779a7; } .action-picked_up .status { background: #e3f0fa; color: #1f5f8b; }
        .action-already_present, .action-completed { border-top-color: #d0a33d; } .action-already_present .status, .action-completed .status { background: #fbf3e0; color: #8a6514; }
        .action-not_authorized { border-top-color: #d64545; } .action-not_authorized .status { background: #fde8e8; color: #a52a2a; }
        .photo { align-items: center; background: #dcefe9; border-radius: 18px; color: #27735e; display: inline-flex; font-size: 3rem; font-weight: 800; justify-content: center; object-fit: cover; overflow: hidden; }
        .photo-contact { height: 200px; width: 200px; }
        .photo-child { height: 220px; width: 220px; }
        .child .relationship { color: #536b7b; margin-top: 8px; }
        .error { background: #fde8e8; border: 2px solid #e6a1a1; border-radius: 20px; color: #a52a2a; font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800; padding: 48px; text-align: center; }
        .scan-input { left: -9999px; opacity: 0; position: absolute; }
        .hidden { display: none !important; }
        @media (max-width: 900px) {
            body { overflow: auto; }
            .kiosk { min-height: 100%; height: auto; }
            .result-layout { grid-template-columns: 1fr; }
            .contact { flex-direction: row; gap: 20px; text-align: left; }
            .contact h2 { margin-top: 4px; }
            .photo-contact { flex: 0 0 auto; height: 150px; width: 150px; }
        }
        @media (max-width: 560px) {
            .kiosk-bar { align-items: flex-start; gap: 12px; padding: 12px 16px; }
            .kiosk-bar > div { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px 12px; }
            .kiosk-bar a { margin-left: 0; }
            .kiosk-main { padding: 16px; }
            .contact { align-items: flex-start; gap: 14px; padding: 16px; }
            .photo-contact { height: 112px; width: 112px; }
            .photo-child { height: 180px; width: 180px; }
            .children { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="kiosk">
    <header class="kiosk-bar">
        <h1>{{ config('app.name') }} · Check-in</h1>
        <div>
            <span id="kiosk-clock" class="clock"></span>
            <a href="{{ route('security.attendance') }}">Manual check-in</a>
            <a href="#" id="kiosk-fullscreen">Full screen</a>
        </div>
    </header>
    <main class="kiosk-main">
        <input id="rfid-input" class="scan-input" autocomplete="off" aria-label="Card number">
        <div id="kiosk-idle" class="idle">
            <div class="pulse" aria-hidden="true">&#128179;</div>
            <h2>Tap your card</h2>
            <p>Hold your card near the reader to drop off or pick up</p>
        </div>
        <div id="kiosk-result" class="result hidden" aria-live="assertive"></div>
    </main>
</div>

<script>
(() => {
    const input = document.getElementById('rfid-input');
    const idle = document.getElementById('kiosk-idle');
    const result = document.getElementById('kiosk-result');
    const clock = document.getElementById('kiosk-clock');
    const scanUrl = @json(route('security.attendance.scan'));
    const csrfToken = @json(csrf_token());
    const RESULT_DISPLAY_MS = 10000;
    // Some readers do not send Enter, so submit once keystrokes stop.
    const INPUT_IDLE_MS = 300;
    let resetTimer;
    let idleTimer;
    let busy = false;
    let audio;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c]));
    const initials = (name) => String(name || '?').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
    const photo = (person, cls) => person?.image_url
        ? `<img class="photo ${cls}" src="${escapeHtml(person.image_url)}" alt="Photo of ${escapeHtml(person.name)}">`
        : `<span class="photo ${cls}" aria-hidden="true">${escapeHtml(initials(person?.name))}</span>`;

    function beep(ok) {
        try {
            audio ??= new (window.AudioContext || window.webkitAudioContext)();
            const tones = ok ? [880, 1320] : [300, 220];
            tones.forEach((frequency, index) => {
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();
                oscillator.frequency.value = frequency;
                gain.gain.value = 0.15;
                oscillator.connect(gain).connect(audio.destination);
                const start = audio.currentTime + index * 0.15;
                oscillator.start(start);
                oscillator.stop(start + 0.13);
            });
        } catch (_) {}
    }

    function tickClock() {
        clock.textContent = new Date().toLocaleTimeString('en-NG', {
            timeZone: 'Africa/Lagos',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
        });
    }

    function focusInput() {
        if (document.activeElement !== input) input.focus({ preventScroll: true });
    }

    function showResult(html) {
        result.innerHTML = html;
        idle.classList.add('hidden');
        result.classList.remove('hidden');
        clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            result.classList.add('hidden');
            result.innerHTML = '';
            idle.classList.remove('hidden');
        }, RESULT_DISPLAY_MS);
    }

    function renderScan(data) {
        const children = data.children.length
            ? data.children.map((child) => `
                <div class="child action-${escapeHtml(child.action)}">
                    ${photo(child, 'photo-child')}
                    <h3>${escapeHtml(child.name)}</h3>
                    <div class="sid">${escapeHtml(child.student_id)}</div>
                    <div class="relationship">Relationship: ${escapeHtml(child.relationship || 'Authorized contact')}</div>
                    <div class="status">${escapeHtml(child.label)}${child.time ? ` · ${escapeHtml(child.time)}` : ''}</div>
                </div>`).join('')
            : '<div class="error">No children are linked to this card.</div>';

        showResult(`
            <div class="result-layout">
                <div class="contact">
                    ${photo(data.contact, 'photo-contact')}
                    <div><div class="label">Card holder</div><h2>${escapeHtml(data.contact.name)}</h2><div class="detail">Authorized pickup contact</div></div>
                </div>
                <div class="children">${children}</div>
            </div>
        `);
    }

    async function submitScan() {
        clearTimeout(idleTimer);
        const uid = input.value.trim();
        input.value = '';
        if (uid.length < 4 || busy) return;
        busy = true;

        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ uid }),
            });

            if (response.status === 401 || response.status === 419) {
                window.location.reload();
                return;
            }

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Scan failed. Please try again.');
            }

            renderScan(data);
            beep(data.children.length > 0 && data.children.every((child) => child.action !== 'not_authorized'));
        } catch (error) {
            showResult(`<div class="error">${escapeHtml(error.message)}</div>`);
            beep(false);
        } finally {
            busy = false;
            focusInput();
        }
    }

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitScan();
        }
    });

    input.addEventListener('input', () => {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(submitScan, INPUT_IDLE_MS);
    });

    document.getElementById('kiosk-fullscreen').addEventListener('click', (event) => {
        event.preventDefault();
        document.documentElement.requestFullscreen?.();
        focusInput();
    });

    document.addEventListener('click', focusInput);
    input.addEventListener('blur', () => setTimeout(focusInput, 0));
    setInterval(focusInput, 2000);
    tickClock();
    setInterval(tickClock, 1000);
    focusInput();
})();
</script>
</body>
</html>
