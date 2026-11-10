@php $title = $invitation->test->nom; @endphp
@extends('layouts.online-test')

@section('content')
<div
    class="min-h-screen"
    x-data="onlineTestExam({
        remaining: {{ (int) $remainingSeconds }},
        timeUrl: @js(route('online-tests.take.time', $invitation->token)),
        cancelUrl: @js(route('online-tests.take.cancel', $invitation->token)),
        submitUrl: @js(route('online-tests.take.submit', $invitation->token)),
        csrf: @js(csrf_token()),
    })"
    x-init="init()"
>
    <div class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto max-w-3xl px-4 py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ $invitation->test->nom }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $invitation->candidat_nom }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Temps restant</p>
                <p class="text-lg font-mono font-semibold" :class="remaining <= 60 ? 'text-red-600' : 'text-slate-900'" x-text="formatTime(remaining)"></p>
            </div>
        </div>
        <div class="h-1 bg-slate-100">
            <div class="h-1 bg-escm-primary transition-all duration-1000" :style="'width:' + progressPct + '%'"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('online-tests.take.submit', $invitation->token) }}" id="exam-form" class="mx-auto max-w-3xl px-4 py-8 space-y-5">
        @csrf
        @foreach($invitation->test->questions as $i => $question)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Question {{ $i + 1 }} · {{ rtrim(rtrim(number_format((float)$question->points, 2, ',', ' '), '0'), ',') }} pts</p>
            <p class="text-sm font-medium text-slate-900 whitespace-pre-wrap mb-4">{{ $question->enonce }}</p>

            @if($question->type === 'qcm')
                <div class="space-y-2">
                    @foreach($question->optionsList() as $opt)
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-escm-primary has-[:checked]:bg-escm-primary/5">
                            <input type="radio" name="reponses[{{ $question->id }}]" value="{{ $opt }}"
                                   class="text-escm-primary focus:ring-escm-primary border-slate-300">
                            <span class="text-sm text-slate-700">{{ $opt }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <textarea name="reponses[{{ $question->id }}]" rows="4" maxlength="5000"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Votre réponse…"></textarea>
            @endif
        </div>
        @endforeach

        <div class="flex justify-end pb-10">
            <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-6 py-3 rounded-lg"
                    onclick="return confirm('Soumettre définitivement vos réponses ?')">
                Soumettre le test
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function onlineTestExam(cfg) {
    return {
        remaining: cfg.remaining,
        initial: cfg.remaining,
        cancelled: false,
        armed: false,
        pollTimer: null,
        tickTimer: null,
        progressPct: 100,
        formatTime(sec) {
            sec = Math.max(0, Math.floor(sec));
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },
        init() {
            this.updateProgress();
            this.ensureFullscreen();
            // Arm anti-cheat after short grace (fullscreen settle)
            setTimeout(() => { this.armed = true; }, 1500);
            this.bindAntiCheat();
            this.tickTimer = setInterval(() => {
                if (this.remaining > 0) this.remaining -= 1;
                this.updateProgress();
                if (this.remaining <= 0) this.autoSubmit();
            }, 1000);
            this.pollTimer = setInterval(() => this.syncTime(), 10000);
            window.addEventListener('beforeunload', this.onBeforeUnload);
        },
        updateProgress() {
            this.progressPct = this.initial > 0 ? Math.max(0, (this.remaining / this.initial) * 100) : 0;
        },
        async ensureFullscreen() {
            try {
                if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                    await document.documentElement.requestFullscreen();
                }
            } catch (_) {}
        },
        bindAntiCheat() {
            const trip = (reason) => this.cancel(reason);
            document.addEventListener('fullscreenchange', () => {
                if (this.armed && !document.fullscreenElement) trip('fullscreen_exit');
            });
            document.addEventListener('visibilitychange', () => {
                if (this.armed && document.hidden) trip('visibility');
            });
            window.addEventListener('blur', () => {
                if (this.armed) trip('tab_change');
            });
        },
        onBeforeUnload(e) {
            if (!this.cancelled) {
                e.preventDefault();
                e.returnValue = '';
            }
        },
        async syncTime() {
            try {
                const res = await fetch(cfg.timeUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                if (typeof data.remaining === 'number') this.remaining = data.remaining;
                this.updateProgress();
                if (data.statut === 'annule') {
                    window.location = @js(route('online-tests.take.show', $invitation->token));
                    return;
                }
                if (data.expired || data.remaining <= 0) this.autoSubmit();
            } catch (_) {}
        },
        async cancel(reason) {
            if (this.cancelled) return;
            this.cancelled = true;
            this.armed = false;
            clearInterval(this.tickTimer);
            clearInterval(this.pollTimer);
            try {
                await fetch(cfg.cancelUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ reason }),
                });
            } catch (_) {}
            window.location = @js(route('online-tests.take.show', $invitation->token));
        },
        autoSubmit() {
            if (this.cancelled) return;
            this.cancelled = true;
            this.armed = false;
            clearInterval(this.tickTimer);
            clearInterval(this.pollTimer);
            document.getElementById('exam-form')?.submit();
        },
    };
}
</script>
@endpush
