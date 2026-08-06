/**
 * Poll personal notifications: play a soft chime on new ones,
 * blink the badge while unread remain unviewed.
 */
(function initNotificationPoller() {
    const bell = document.getElementById('notif-bell');
    const badge = document.getElementById('notif-badge');
    if (!bell || !badge) return;

    const pollUrl = bell.dataset.pollUrl;
    const markReadUrl = bell.dataset.markReadUrl;
    const csrf = bell.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content;
    if (!pollUrl || !markReadUrl) return;

    let latestId = parseInt(bell.dataset.initialLatest || '0', 10) || 0;
    let unread = parseInt(bell.dataset.initialUnread || '0', 10) || 0;
    let audioCtx = null;
    let started = false;

    const styleId = 'notif-blink-style';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            @keyframes notif-badge-blink {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.35; transform: scale(1.15); }
            }
            .notif-badge-blink {
                animation: notif-badge-blink 0.9s ease-in-out infinite;
            }
        `;
        document.head.appendChild(style);
    }

    function ensureAudio() {
        if (!audioCtx) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().catch(() => {});
        }
        return audioCtx;
    }

    function playChime() {
        const ctx = ensureAudio();
        if (!ctx) return;

        const now = ctx.currentTime;
        const notes = [
            { freq: 880, start: 0, dur: 0.12 },
            { freq: 1174.66, start: 0.1, dur: 0.18 },
        ];

        notes.forEach(({ freq, start, dur }) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now + start);
            gain.gain.exponentialRampToValueAtTime(0.12, now + start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + start + dur);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now + start);
            osc.stop(now + start + dur + 0.02);
        });
    }

    function renderBadge() {
        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : String(unread);
            badge.classList.remove('hidden');
            badge.classList.add('flex', 'notif-badge-blink');
        } else {
            badge.textContent = '';
            badge.classList.add('hidden');
            badge.classList.remove('flex', 'notif-badge-blink');
        }
    }

    async function poll() {
        try {
            const res = await fetch(pollUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            const newLatest = parseInt(data.latest_id || 0, 10) || 0;
            const newUnread = parseInt(data.unread || 0, 10) || 0;

            if (started && newLatest > latestId) {
                playChime();
            }

            latestId = newLatest;
            unread = newUnread;
            renderBadge();
            started = true;
        } catch (_) {
            // silent — next poll will retry
        }
    }

    async function markRead() {
        try {
            await fetch(markReadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: '{}',
            });
            unread = 0;
            renderBadge();
        } catch (_) {
            // ignore
        }
    }

    function maybeMarkReadOnAlertes() {
        if (window.location.hash === '#alertes') {
            markRead();
        }
    }

    bell.addEventListener('click', () => {
        ensureAudio();
        markRead();
    });

    window.addEventListener('hashchange', maybeMarkReadOnAlertes);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) poll();
    });

    renderBadge();
    maybeMarkReadOnAlertes();
    poll();
    setInterval(poll, 15000);
})();
