/**
 * Floating, draggable CRM task due-notification bubble (Messenger-style).
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('crmTaskBubble', (config = {}) => ({
        open: false,
        count: 0,
        tasks: [],
        dragging: false,
        moved: false,
        x: null,
        y: null,
        startX: 0,
        startY: 0,
        originX: 0,
        originY: 0,
        pollUrl: config.pollUrl || '',
        storageKey: 'escm_crm_task_bubble_pos',
        pollTimer: null,
        size: 56,

        init() {
            this.restorePosition();
            if (!this.pollUrl) return;
            this.refresh();
            this.pollTimer = setInterval(() => this.refresh(), 20000);
            window.addEventListener('resize', () => this.clamp());
        },

        destroy() {
            if (this.pollTimer) clearInterval(this.pollTimer);
        },

        restorePosition() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (!raw) return;
                const pos = JSON.parse(raw);
                if (typeof pos.x === 'number' && typeof pos.y === 'number') {
                    this.x = pos.x;
                    this.y = pos.y;
                    this.$nextTick(() => this.clamp());
                }
            } catch (_) {}
        },

        savePosition() {
            if (this.x === null || this.y === null) return;
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({ x: this.x, y: this.y }));
            } catch (_) {}
        },

        style() {
            if (this.x === null || this.y === null) {
                return { right: '1.25rem', bottom: '1.25rem', left: 'auto', top: 'auto' };
            }
            return {
                left: `${this.x}px`,
                top: `${this.y}px`,
                right: 'auto',
                bottom: 'auto',
            };
        },

        panelStyle() {
            if (this.x === null || this.y === null) {
                return { right: '1.25rem', bottom: '5.5rem', left: 'auto', top: 'auto' };
            }
            const panelW = Math.min(window.innerWidth - 32, 320);
            const left = Math.min(Math.max(8, this.x - panelW + this.size), window.innerWidth - panelW - 8);
            const top = Math.max(16, this.y - 290);
            return { left: `${left}px`, top: `${top}px`, right: 'auto', bottom: 'auto' };
        },

        clamp() {
            if (this.x === null || this.y === null) return;
            const maxX = Math.max(8, window.innerWidth - this.size - 8);
            const maxY = Math.max(8, window.innerHeight - this.size - 8);
            this.x = Math.min(Math.max(8, this.x), maxX);
            this.y = Math.min(Math.max(8, this.y), maxY);
            this.savePosition();
        },

        onPointerDown(e) {
            if (e.button !== undefined && e.button !== 0) return;
            this.dragging = true;
            this.moved = false;
            const rect = this.$refs.bubble.getBoundingClientRect();
            this.x = rect.left;
            this.y = rect.top;
            this.originX = this.x;
            this.originY = this.y;
            this.startX = e.clientX;
            this.startY = e.clientY;
            e.currentTarget.setPointerCapture?.(e.pointerId);
        },

        onPointerMove(e) {
            if (!this.dragging) return;
            const dx = e.clientX - this.startX;
            const dy = e.clientY - this.startY;
            if (Math.abs(dx) > 4 || Math.abs(dy) > 4) this.moved = true;
            this.x = this.originX + dx;
            this.y = this.originY + dy;
            this.clamp();
        },

        onPointerUp() {
            if (!this.dragging) return;
            this.dragging = false;
            this.savePosition();
        },

        toggle() {
            if (this.moved) {
                this.moved = false;
                return;
            }
            this.open = !this.open;
            if (this.open) this.refresh();
        },

        async refresh() {
            if (!this.pollUrl) return;
            try {
                const res = await fetch(this.pollUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.count = data.count || 0;
                this.tasks = Array.isArray(data.tasks) ? data.tasks : [];
                if ((data.new_due || 0) > 0) {
                    this.open = true;
                    this.playPulse();
                }
            } catch (_) {}
        },

        playPulse() {
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                const ctx = new Ctx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 740;
                gain.gain.value = 0.08;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } catch (_) {}
        },
    }));
});
