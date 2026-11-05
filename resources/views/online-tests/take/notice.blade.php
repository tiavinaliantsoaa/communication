@php $title = 'Règles du test'; @endphp
@extends('layouts.online-test')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10">
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

        <form action="{{ route('online-tests.take.start', $invitation->token) }}" method="POST"
              x-data="{
                  accepted: false,
                  fullscreenOk: false,
                  error: '',
                  async enterFullscreen() {
                      this.error = '';
                      try {
                          const el = document.documentElement;
                          if (el.requestFullscreen) await el.requestFullscreen();
                          else if (el.webkitRequestFullscreen) await el.webkitRequestFullscreen();
                          this.fullscreenOk = true;
                      } catch (e) {
                          this.error = 'Impossible d’activer le plein écran. Autorisez-le dans votre navigateur.';
                          this.fullscreenOk = false;
                      }
                  }
              }"
              class="space-y-4"
              @submit="if (!fullscreenOk) { $event.preventDefault(); error = 'Activez d’abord le mode plein écran.'; }">
            @csrf
            <label class="flex items-start gap-3 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="accepte_regles" value="1" x-model="accepted" required
                       class="mt-0.5 rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                <span>J’ai lu et j’accepte les règles ci-dessus. Je comprends qu’une action suspecte annulera mon test.</span>
            </label>
            @error('accepte_regles')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            <input type="hidden" name="fullscreen_ok" :value="fullscreenOk ? '1' : ''">
            @error('fullscreen_ok')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="button" @click="enterFullscreen()"
                        class="inline-flex justify-center items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <span x-text="fullscreenOk ? 'Plein écran activé ✓' : 'Activer le plein écran'"></span>
                </button>
                <button type="submit"
                        :disabled="!accepted || !fullscreenOk"
                        class="inline-flex justify-center items-center rounded-lg bg-escm-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-escm-primary-dark disabled:opacity-40 disabled:cursor-not-allowed">
                    Commencer le test
                </button>
            </div>
            <p x-show="error" x-cloak class="text-xs text-red-600" x-text="error"></p>
        </form>
    </div>
</div>
@endsection
