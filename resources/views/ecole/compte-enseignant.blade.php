@extends('layouts.app')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="text-sm font-semibold text-slate-900 mb-1">Compte enseignant</h3>
    <p class="text-sm text-slate-500 mb-4">Choisissez un enseignant pour voir son tableau de bord. Le mot de passe d’un compte existant peut être réinitialisé ici.</p>
    <div class="divide-y divide-slate-100 border-t border-slate-100">
        @forelse($enseignants as $enseignant)
            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('ecole.compte-enseignant', ['enseignant' => $enseignant->id]) }}" class="text-sm font-medium text-slate-900 hover:text-escm-primary">{{ $enseignant->nom_complet }}</a>
                    <p class="text-xs text-slate-500">{{ $enseignant->compte?->email ?: 'Aucun compte' }}</p>
                </div>
                @if($enseignant->compte)
                    <form method="POST" action="{{ route('ecole.compte-enseignant.password', $enseignant) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center"
                          x-data="{ motDePasse: '', copie: false,
                              generer() {
                                  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                                  const bytes = new Uint8Array(12);
                                  crypto.getRandomValues(bytes);
                                  this.motDePasse = Array.from(bytes, (byte) => alphabet[byte % alphabet.length]).join('');
                                  this.copie = false;
                              },
                              async copier() {
                                  try {
                                      await navigator.clipboard.writeText(this.motDePasse);
                                      this.copie = true;
                                      setTimeout(() => { this.copie = false; }, 2000);
                                  } catch (e) {
                                      this.copie = false;
                                  }
                              }
                          }">
                        @csrf
                        <input type="text" name="password" x-model="motDePasse" required minlength="8" maxlength="100" autocomplete="new-password" placeholder="Nouveau mot de passe" class="w-full sm:w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                        <button type="button" @click="motDePasse ? copier() : generer()" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50" x-text="motDePasse ? (copie ? 'Mot de passe copié' : 'Copier le mot de passe') : 'Générer un mot de passe'">Générer un mot de passe</button>
                        <button type="submit" class="rounded-lg bg-escm-primary px-3 py-2 text-xs font-medium text-white hover:bg-escm-primary-dark">Réinitialiser</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="py-4 text-sm text-slate-500">Aucun enseignant pour le moment.</p>
        @endforelse
    </div>
</div>
@endsection
