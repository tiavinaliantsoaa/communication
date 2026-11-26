@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Nouvel enseignant</h3>
        <form method="POST" action="{{ route('ecole.enseignants.store') }}" class="space-y-4"
              x-data="{
                  compte: @js((bool) old('creer_compte_enseignant')),
                  motDePasse: @js(old('enseignant_password', '')),
                  copie: false,
                  genererMotDePasse() {
                      const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                      const bytes = new Uint8Array(12);
                      crypto.getRandomValues(bytes);
                      this.motDePasse = Array.from(bytes, (byte) => alphabet[byte % alphabet.length]).join('');
                      this.copie = false;
                  },
                  async copierMotDePasse() {
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
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Prénom</label>
                <input type="text" name="prenom" value="{{ old('prenom') }}" required maxlength="80" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('prenom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom</label>
                <input type="text" name="nom" value="{{ old('nom') }}" required maxlength="80" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="creer_compte_enseignant" value="1" x-model="compte" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                Créer un compte enseignant
            </label>
            <div x-show="compte" x-cloak class="space-y-4">
                <div>
                    <label for="enseignant-email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input id="enseignant-email" type="email" name="enseignant_email" value="{{ old('enseignant_email') }}" :required="compte" maxlength="255" autocomplete="off" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                    @error('enseignant_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="enseignant-password" class="block text-sm font-medium text-slate-700 mb-1.5">Mot de passe</label>
                    <input id="enseignant-password" type="text" name="enseignant_password" x-model="motDePasse" :required="compte" minlength="8" maxlength="100" autocomplete="new-password" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                    <button type="button" @click="motDePasse ? copierMotDePasse() : genererMotDePasse()" class="mt-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" x-text="motDePasse ? (copie ? 'Mot de passe copié' : 'Copier le mot de passe') : 'Générer un mot de passe'">Générer un mot de passe</button>
                    @error('enseignant_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
        </form>
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Enseignant</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($enseignants as $enseignant)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $enseignant->nom_complet }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('ecole.enseignants.destroy', $enseignant) }}" onsubmit="return confirm('Supprimer cet enseignant ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-8 text-center text-slate-500">Aucun enseignant pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
