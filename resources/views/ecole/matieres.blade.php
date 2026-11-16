@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Nouvelle matière</h3>
        <form method="POST" action="{{ route('ecole.matieres.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom</label>
                <input type="text" name="nom" value="{{ old('nom') }}" required maxlength="120" placeholder="Ex. COM & PLAN" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
        </form>
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Matière</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($matieres as $matiere)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $matiere->nom }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('ecole.matieres.destroy', $matiere) }}" onsubmit="return confirm('Supprimer cette matière ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-8 text-center text-slate-500">Aucune matière pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
