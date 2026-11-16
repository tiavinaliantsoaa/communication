@php
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary';
@endphp

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Nouvel étudiant</h3>
        @if($groupes->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord un groupe.</p>
        @else
            <form method="POST" action="{{ route('ecole.etudiants.store') }}" class="space-y-4"
                  x-data="{
                      q: '',
                      prenom: @js(old('prenom', '')),
                      nom: @js(old('nom', '')),
                      selectedId: @js((string) old('crm_candidate_id', '')),
                      open: false,
                      results: [],
                      loading: false,
                      timer: null,
                      url: @js(route('ecole.etudiants.crm')),
                      search() {
                          clearTimeout(this.timer);
                          this.timer = setTimeout(() => this.fetchResults(), 250);
                      },
                      async fetchResults() {
                          this.loading = true;
                          this.open = true;
                          try {
                              const response = await fetch(this.url + '?q=' + encodeURIComponent(this.q), {
                                  headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                  credentials: 'same-origin'
                              });
                              this.results = response.ok ? await response.json() : [];
                          } catch (e) {
                              this.results = [];
                          }
                          this.loading = false;
                      },
                      choose(item) {
                          this.selectedId = String(item.id);
                          this.q = item.label;
                          this.prenom = item.prenom;
                          this.nom = item.nom;
                          this.open = false;
                      },
                      onType() {
                          this.selectedId = '';
                          this.search();
                      }
                  }">
                @csrf
                <div class="relative" @click.outside="open = false">
                    <label for="etudiant-via-crm" class="block text-sm font-medium text-slate-700 mb-1.5">Étudiant via CRM</label>
                    <input id="etudiant-via-crm" type="search" x-model="q" @input="onType()" @focus="fetchResults()" autocomplete="off" placeholder="Nom, prénom ou téléphone" class="{{ $input }}">
                    <input type="hidden" name="crm_candidate_id" :value="selectedId">
                    <p class="mt-1 text-xs text-slate-500">Candidats déjà inscrits. La fiche CRM indiquera Inscris dans Groupe, suivi du groupe choisi.</p>
                    @error('crm_candidate_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <div x-show="open" x-cloak class="absolute left-0 right-0 top-full z-20 mt-1 max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg py-1">
                        <p x-show="loading" class="px-3 py-2 text-sm text-slate-500">Recherche…</p>
                        <template x-for="item in results" :key="item.id">
                            <button type="button" @click="choose(item)" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                <span x-text="item.label"></span>
                                <span class="text-xs text-slate-400" x-text="item.programme || ''"></span>
                            </button>
                        </template>
                        <p x-show="!loading && results.length === 0" class="px-3 py-2 text-sm text-slate-500">Aucun candidat inscrit.</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prénom</label>
                    <input type="text" name="prenom" x-model="prenom" required maxlength="100" class="{{ $input }}">
                    @error('prenom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom</label>
                    <input type="text" name="nom" x-model="nom" required maxlength="100" class="{{ $input }}">
                    @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Groupe</label>
                    <select name="ecole_groupe_id" class="{{ $input }}" required>
                        @foreach($groupes as $groupe)
                            <option value="{{ $groupe->id }}" @selected((int) old('ecole_groupe_id') === (int) $groupe->id)>{{ $groupe->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
            </form>
        @endif
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Étudiant</th>
                        <th class="px-3 py-3">Groupe</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($etudiants as $etudiant)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $etudiant->nom_complet }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $etudiant->groupe->nom ?? '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('ecole.etudiants.destroy', $etudiant) }}" onsubmit="return confirm('Supprimer cet étudiant ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-slate-500">Aucun étudiant pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
