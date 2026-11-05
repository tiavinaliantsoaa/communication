@php $title = $invitation->test->nom; @endphp
@extends('layouts.online-test')

@section('content')
<div
    class="min-h-screen"
    x-data="onlineTestSession({
        phase: @js($phase),
        remaining: {{ (int) $remainingSeconds }},
        dureeMinutes: {{ (int) $invitation->test->duree_minutes }},
        startUrl: @js(route('online-tests.take.start', $invitation->token)),
        timeUrl: @js(route('online-tests.take.time', $invitation->token)),
        cancelUrl: @js(route('online-tests.take.cancel', $invitation->token)),
        doneUrl: @js(route('online-tests.take.show', $invitation->token)),
        csrf: @js(csrf_token()),
    })"
    x-init="init()"
>
    {{-- Notice / règles --}}
    <div x-show="phase === 'notice'" x-cloak class="mx-auto max-w-2xl px-4 py-10">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-escm-primary mb-2">Test en ligne ESCM</p>
            <h1 class="text-xl font-semibold text-slate-900 mb-1">{{ $invitation->test->nom }}</h1>
            <p class="text-sm text-slate-500 mb-6">Bonjour {{ $invitation->candidat_nom }} — durée : {{ $invitation->test->duree_minutes }} minutes</p>

            @if($invitation->test->description)
                <div class="mb-6 rounded-lg bg-slate-50 border border-slate-100 px-4 py-3 text-sm text-slate-700 whitespace-pre-wrap">{{ $invitation->test->description }}</div>
            @endif

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 mb-6">
                <h2 class="text-sm font-semibold text-amber-900 mb-2">Règles anti-triche — lisez attentivement</h2>
                <ul class="text-sm text-amber-900/90 space-y-1.5 list-disc pl-5">
                    <li>Ce lien est <strong>à usage unique</strong> : une seule tentative.</li>
                    <li>Le test ne peut démarrer qu’en <strong>mode plein écran</strong>.</li>
                    <li>Toute <strong>sortie du plein écran</strong>, changement d’onglet ou perte de focus <strong>annule immédiatement</strong> le test.</li>
                    <li>La minuterie est gérée par le serveur : impossible de la remettre à zéro.</li>
                    <li>À la fin du temps, vos réponses sont enregistrées automatiquement si vous soumettez, sinon la session expire.</li>
                </ul>
            </div>

            <div class="space-y-4">
                <label class="flex items-start gap-3 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" x-model="accepted" class="mt-0.5 rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                    <span>J’ai lu et j’accepte les règles ci-dessus. Je comprends qu’une action suspecte annulera mon test.</span>
                </label>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="button" @click="enterFullscreen()"
                            class="inline-flex justify-center items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <span x-text="fullscreenOk ? 'Plein écran activé ✓' : 'Activer le plein écran'"></span>
                    </button>
                    <button type="button"
                            @click="startExam()"
                            :disabled="!accepted || !fullscreenOk || starting"
                            class="inline-flex justify-center items-center rounded-lg bg-escm-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-escm-primary-dark disabled:opacity-40 disabled:cursor-not-allowed">
                        <span x-text="starting ? 'Démarrage…' : 'Commencer le test'"></span>
                    </button>
                </div>
                <p x-show="error" x-cloak class="text-xs text-red-600" x-text="error"></p>
            </div>
        </div>
    </div>

    {{-- Reprise après refresh : réactiver le plein écran --}}
    <div x-show="phase === 'exam' && !fullscreenReady" x-cloak class="mx-auto max-w-lg px-4 py-16 text-center">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
            <h2 class="text-lg font-semibold text-slate-900 mb-2">Plein écran requis</h2>
            <p class="text-sm text-slate-600 mb-6">Réactivez le mode plein écran pour continuer votre test en cours.</p>
            <button type="button" @click="resumeFullscreen()"
                    class="inline-flex justify-center items-center rounded-lg bg-escm-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-escm-primary-dark">
                Continuer en plein écran
            </button>
            <p x-show="error" x-cloak class="mt-3 text-xs text-red-600" x-text="error"></p>
        </div>
    </div>

    {{-- Examen --}}
    <div x-show="phase === 'exam' && fullscreenReady" x-cloak>
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

        <form method="POST" action="{{ route('online-tests.take.submit', $invitation->token) }}" id="exam-form"
              class="mx-auto max-w-3xl px-4 py-8 space-y-5"
              @submit="prepareLeave()">
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
                <button type="submit"
                        class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-6 py-3 rounded-lg"
                        @click="if (!confirm('Soumettre définitivement vos réponses ?')) { $event.preventDefault(); }">
                    Soumettre le test
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function onlineTestSession(cfg) {
    return {
        phase: cfg.phase,
        remaining: cfg.remaining,
        initial: cfg.remaining || (cfg.dureeMinutes * 60),
        accepted: false,
        fullscreenOk: false,
        fullscreenReady: cfg.phase === 'exam' ? false : true,
        starting: false,
        error: '',
        armed: false,
        allowLeave: false,
        leaving: false,
        pollTimer: null,
        tickTimer: null,
        progressPct: 100,
        boundBeforeUnload: null,

        formatTime(sec) {
            sec = Math.max(0, Math.floor(sec));
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },

        init() {
            this.boundBeforeUnload = (e) => this.onBeforeUnload(e);
            if (this.phase === 'exam') {
                // Attendre un clic utilisateur pour réactiver le plein écran (politique navigateur)
                this.fullscreenReady = false;
            }
            document.addEventListener('fullscreenchange', () => this.onFullscreenChange());
            document.addEventListener('visibilitychange', () => {
                if (this.armed && document.hidden) this.cancel('visibility');
            });
            window.addEventListener('blur', () => {
                if (this.armed) this.cancel('tab_change');
            });
        },

        updateProgress() {
            this.progressPct = this.initial > 0 ? Math.max(0, (this.remaining / this.initial) * 100) : 0;
        },

        async enterFullscreen() {
            this.error = '';
            try {
                const el = document.documentElement;
                if (!document.fullscreenElement) {
                    if (el.requestFullscreen) await el.requestFullscreen();
                    else if (el.webkitRequestFullscreen) await el.webkitRequestFullscreen();
                }
                this.fullscreenOk = !!document.fullscreenElement || !!document.webkitFullscreenElement;
                if (!this.fullscreenOk) {
                    this.error = 'Le plein écran n’est pas actif. Réessayez.';
                }
            } catch (e) {
                this.fullscreenOk = false;
                this.error = 'Impossible d’activer le plein écran. Autorisez-le dans votre navigateur.';
            }
        },

        async resumeFullscreen() {
            await this.enterFullscreen();
            if (!this.fullscreenOk) return;
            this.fullscreenReady = true;
            this.beginExamTimers();
        },

        onFullscreenChange() {
            const active = !!(document.fullscreenElement || document.webkitFullscreenElement);
            if (this.phase === 'notice') {
                this.fullscreenOk = active;
                return;
            }
            if (this.armed && !active) {
                this.cancel('fullscreen_exit');
            }
        },

        async startExam() {
            if (!this.accepted || !this.fullscreenOk || this.starting) return;
            this.error = '';
            this.starting = true;

            // S'assurer que le plein écran est toujours actif juste avant le démarrage
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                await this.enterFullscreen();
                if (!this.fullscreenOk) {
                    this.starting = false;
                    return;
                }
            }

            try {
                const body = new FormData();
                body.append('_token', cfg.csrf);
                body.append('accepte_regles', '1');
                body.append('fullscreen_ok', '1');

                const res = await fetch(cfg.startUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body,
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    const msg = data.message
                        || (data.errors && Object.values(data.errors).flat()[0])
                        || 'Impossible de démarrer le test.';
                    this.error = msg;
                    this.starting = false;
                    return;
                }

                const data = await res.json();
                this.remaining = typeof data.remaining === 'number' ? data.remaining : (cfg.dureeMinutes * 60);
                this.initial = this.remaining;
                this.phase = 'exam';
                this.fullscreenReady = true;
                this.starting = false;
                this.beginExamTimers();
            } catch (e) {
                this.error = 'Erreur réseau. Réessayez.';
                this.starting = false;
            }
        },

        beginExamTimers() {
            this.updateProgress();
            // Court délai pour stabiliser le plein écran avant d’armer l’anti-triche
            setTimeout(() => { this.armed = true; }, 800);
            if (this.tickTimer) clearInterval(this.tickTimer);
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.tickTimer = setInterval(() => {
                if (this.remaining > 0) this.remaining -= 1;
                this.updateProgress();
                if (this.remaining <= 0) this.autoSubmit();
            }, 1000);
            this.pollTimer = setInterval(() => this.syncTime(), 10000);
            window.addEventListener('beforeunload', this.boundBeforeUnload);
        },

        prepareLeave() {
            this.allowLeave = true;
            this.armed = false;
            this.leaving = true;
            if (this.tickTimer) clearInterval(this.tickTimer);
            if (this.pollTimer) clearInterval(this.pollTimer);
            window.removeEventListener('beforeunload', this.boundBeforeUnload);
        },

        onBeforeUnload(e) {
            if (this.allowLeave || this.leaving) return;
            e.preventDefault();
            e.returnValue = '';
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
                    this.prepareLeave();
                    window.location = cfg.doneUrl;
                    return;
                }
                if (data.expired || data.remaining <= 0) this.autoSubmit();
            } catch (_) {}
        },

        async cancel(reason) {
            if (this.allowLeave || this.leaving) return;
            this.leaving = true;
            this.armed = false;
            this.allowLeave = true;
            if (this.tickTimer) clearInterval(this.tickTimer);
            if (this.pollTimer) clearInterval(this.pollTimer);
            window.removeEventListener('beforeunload', this.boundBeforeUnload);
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
            window.location = cfg.doneUrl;
        },

        autoSubmit() {
            if (this.leaving || this.allowLeave) return;
            this.prepareLeave();
            document.getElementById('exam-form')?.submit();
        },
    };
}
</script>
@endpush
