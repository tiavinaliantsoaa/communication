@php
    $title = $title ?? 'Gestion de projet';
    $subtitle = $subtitle ?? '';
    $labelColors = [
        'yellow' => 'bg-yellow-400',
        'blue' => 'bg-blue-500',
        'red' => 'bg-red-500',
        'green' => 'bg-emerald-500',
        'cyan' => 'bg-cyan-400',
        'purple' => 'bg-purple-500',
    ];
    $bgUrl = $tableau->background_url;
@endphp

@extends('layouts.app')

@section('content')
<div
    class="-mx-4 sm:-mx-6 lg:-mx-8 -mt-2 relative"
    x-data="projetBoard()"
    x-init="init()"
>
    {{-- Board background --}}
    <div
        class="absolute inset-0 -z-10 pointer-events-none"
        @if($bgUrl)
            style="background-image: url('{{ $bgUrl }}'); background-size: cover; background-position: center;"
        @endif
    >
        @if($bgUrl)
            <div class="absolute inset-0 bg-slate-900/25"></div>
        @endif
    </div>

    {{-- Toolbar: background --}}
    <div class="px-4 sm:px-6 lg:px-8 pt-1 pb-3 flex flex-wrap items-center gap-2">
        <form action="{{ route('gestion-projet.background') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
            @csrf
            <label class="inline-flex items-center gap-2 rounded-lg bg-white/90 hover:bg-white border border-slate-200 shadow-sm px-3 py-2 text-xs font-semibold text-slate-700 cursor-pointer">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Image de fond
                <input type="file" name="background" accept="image/*" class="hidden" onchange="this.form.submit()">
            </label>
            @if($bgUrl)
                <button type="submit" name="remove" value="1" class="inline-flex items-center rounded-lg bg-white/90 hover:bg-white border border-slate-200 shadow-sm px-3 py-2 text-xs font-semibold text-red-600">
                    Retirer le fond
                </button>
            @endif
        </form>
    </div>

    {{-- Board --}}
    <div class="overflow-x-auto px-4 sm:px-6 lg:px-8 pb-6">
        <div class="flex gap-4 min-w-max items-start">
            @foreach($listes as $liste)
                <div class="w-72 flex-shrink-0 flex flex-col max-h-[calc(100vh-12rem)] bg-slate-200/85 backdrop-blur-sm rounded-xl shadow-sm">
                    <div class="px-3 py-3 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-800 truncate">{{ $liste->nom }}</h3>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="text-xs font-medium text-slate-500 bg-slate-300/60 rounded-full px-2 py-0.5">{{ $liste->cartes->count() }}</span>
                            <div class="relative" x-data="{ open: false }">
                                <button type="button" @click="open = !open" class="p-1 rounded text-slate-500 hover:bg-slate-300/50">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                </button>
                                <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-1 w-40 rounded-lg bg-white border border-slate-200 shadow-lg z-20 py-1">
                                    <button type="button" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50" @click="renameListe({{ $liste->id }}, @js($liste->nom)); open = false">Renommer</button>
                                    <button type="button" class="w-full text-left px-3 py-1.5 text-xs text-red-600 hover:bg-red-50" @click="deleteListe({{ $liste->id }}); open = false">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex-1 overflow-y-auto px-2 pb-2 space-y-2 min-h-[4rem] sortable-list"
                        data-liste-id="{{ $liste->id }}"
                    >
                        @foreach($liste->cartes as $carte)
                            @php $progress = $carte->checklistProgress(); @endphp
                            <article
                                data-id="{{ $carte->id }}"
                                @click="openCard({{ $carte->id }})"
                                class="group cursor-pointer bg-white rounded-lg border border-slate-200 shadow-sm hover:border-escm-primary/40 hover:shadow-md transition p-3"
                            >
                                @if($carte->etiquettes->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach($carte->etiquettes as $etiquette)
                                            <span
                                                class="h-2 w-10 rounded-full {{ $labelColors[$etiquette->couleur] ?? 'bg-slate-400' }}"
                                                title="{{ $etiquette->nom }}"
                                            ></span>
                                        @endforeach
                                    </div>
                                @endif

                                <p class="text-sm font-medium text-slate-900 leading-snug">{{ $carte->titre }}</p>

                                <div class="mt-2.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                    @if($carte->dateBadgeLabel())
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded px-1.5 py-0.5 font-medium',
                                            'bg-red-100 text-red-700' => $carte->isOverdue(),
                                            'bg-emerald-100 text-emerald-700' => $carte->isDone() && $carte->date_fin,
                                            'bg-slate-100 text-slate-600' => ! $carte->isOverdue() && ! ($carte->isDone() && $carte->date_fin),
                                        ])>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ $carte->dateBadgeLabel() }}
                                        </span>
                                    @endif

                                    @if($carte->description)
                                        <span title="Description" class="inline-flex">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h10"/></svg>
                                        </span>
                                    @endif

                                    @if($carte->commentaires->count())
                                        <span class="inline-flex items-center gap-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            {{ $carte->commentaires->count() }}
                                        </span>
                                    @endif

                                    @if($carte->piecesJointes->count())
                                        <span class="inline-flex items-center gap-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            {{ $carte->piecesJointes->count() }}
                                        </span>
                                    @endif

                                    @if($progress['total'] > 0)
                                        <span @class([
                                            'inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 font-medium',
                                            'bg-emerald-100 text-emerald-700' => $progress['done'] === $progress['total'],
                                            'bg-slate-100 text-slate-600' => $progress['done'] !== $progress['total'],
                                        ])>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ $progress['done'] }}/{{ $progress['total'] }}
                                        </span>
                                    @endif
                                </div>

                                @if($carte->membres->isNotEmpty())
                                    <div class="mt-2.5 flex -space-x-1.5 justify-end">
                                        @foreach($carte->membres->take(4) as $membre)
                                            <x-user-avatar :user="$membre" size="xs" :ring="true" />
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="p-2">
                        <form @submit.prevent="addCard($event, {{ $liste->id }})" class="flex gap-1">
                            <input
                                type="text"
                                name="titre"
                                placeholder="+ Ajouter une carte"
                                class="w-full rounded-lg border-0 bg-transparent text-sm placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-escm-primary/30 px-2 py-2"
                                required
                            >
                        </form>
                    </div>
                </div>
            @endforeach

            {{-- Add list --}}
            <div class="w-72 flex-shrink-0" x-data="{ adding: false }">
                <button
                    type="button"
                    x-show="!adding"
                    @click="adding = true; $nextTick(() => $refs.listeNom.focus())"
                    class="w-full text-left rounded-xl px-3 py-3 text-sm font-medium text-sky-700 hover:text-sky-800 hover:bg-white/70 transition"
                >
                    + Ajoutez une autre liste
                </button>
                <form
                    x-show="adding"
                    x-cloak
                    @submit.prevent="addListe($event); adding = false"
                    class="rounded-xl bg-slate-200/85 backdrop-blur-sm p-2 shadow-sm"
                >
                    <input
                        x-ref="listeNom"
                        type="text"
                        name="nom"
                        placeholder="Nom de la liste…"
                        class="w-full rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary mb-2"
                        required
                    >
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-3 py-2">Ajouter</button>
                        <button type="button" @click="adding = false" class="text-slate-500 hover:text-slate-700 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal carte --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 sm:p-8"
        @keydown.escape.window="closeCard()"
    >
        <div class="absolute inset-0" @click="closeCard()"></div>

        <div
            x-show="open"
            x-transition
            class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden"
            @click.stop
        >
            <template x-if="loading">
                <div class="p-12 text-center text-slate-500">Chargement…</div>
            </template>

            <template x-if="!loading && card">
                <div>
                    <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50">
                        <div class="flex items-center gap-2 min-w-0">
                            <select
                                x-model="card.projet_liste_id"
                                @change="saveField({ projet_liste_id: card.projet_liste_id })"
                                class="rounded-md border-slate-200 text-xs font-semibold text-slate-700 focus:border-escm-primary focus:ring-escm-primary"
                            >
                                <template x-for="l in card.listes" :key="l.id">
                                    <option :value="l.id" x-text="l.nom" :selected="l.id === card.projet_liste_id"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="confirmDelete()" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50" title="Supprimer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <button type="button" @click="closeCard()" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-0">
                        <div class="lg:col-span-3 p-5 space-y-5 border-b lg:border-b-0 lg:border-r border-slate-100">
                            <input
                                type="text"
                                x-model="card.titre"
                                @change="saveField({ titre: card.titre })"
                                class="w-full border-0 border-b border-transparent focus:border-escm-primary focus:ring-0 text-xl font-bold text-slate-900 px-0"
                            >

                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="showMembers = !showMembers" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">+ Membres</button>
                                <button type="button" @click="showLabels = !showLabels" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">+ Étiquettes</button>
                                <button type="button" @click="addChecklist()" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">Checklist</button>
                                <button type="button" @click="showAttach = !showAttach" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">Pièce jointe</button>
                            </div>

                            <div x-show="showMembers" x-cloak class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                                <p class="text-xs font-semibold text-slate-600 mb-2">Membres</p>
                                <div class="space-y-1 max-h-40 overflow-y-auto">
                                    @foreach($users as $user)
                                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white rounded px-2 py-1">
                                            <input type="checkbox" value="{{ $user->id }}"
                                                   :checked="card.membres.some(m => m.id === {{ $user->id }})"
                                                   @change="toggleMember({{ $user->id }})">
                                            <x-user-avatar :user="$user" size="xs" />
                                            {{ $user->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div x-show="showLabels" x-cloak class="rounded-lg border border-slate-200 p-3 bg-slate-50 space-y-2">
                                <p class="text-xs font-semibold text-slate-600">Étiquettes disponibles</p>
                                <p class="text-[11px] text-slate-500">Cochez pour les assigner à cette carte.</p>
                                <div class="space-y-1">
                                    <template x-for="etiquette in availableLabels" :key="etiquette.id">
                                        <label class="flex items-center gap-2 text-sm cursor-pointer rounded px-2 py-1.5" :class="etiquette.classes.badge">
                                            <input type="checkbox"
                                                   :checked="card.etiquettes.some(e => e.id === etiquette.id)"
                                                   @change="toggleLabel(etiquette.id)">
                                            <span x-text="etiquette.nom"></span>
                                        </label>
                                    </template>
                                    <p x-show="!availableLabels.length" class="text-sm text-slate-500 py-1">Aucune étiquette pour le moment. Créez-en une ci-dessous.</p>
                                </div>
                                <form @submit.prevent="createLabel($event)" class="flex flex-wrap gap-2 pt-2 border-t border-slate-200">
                                    <input type="text" name="nom" placeholder="Nouvelle étiquette…" required maxlength="100"
                                           class="flex-1 min-w-[8rem] rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
                                    <select name="couleur" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
                                        <option value="red">Rouge</option>
                                        <option value="yellow">Jaune</option>
                                        <option value="blue">Bleu</option>
                                        <option value="green">Vert</option>
                                        <option value="cyan">Cyan</option>
                                        <option value="purple">Violet</option>
                                    </select>
                                    <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-3 py-2">Créer</button>
                                </form>
                            </div>

                            <div x-show="showAttach" x-cloak class="rounded-lg border border-slate-200 p-3 bg-slate-50 space-y-2">
                                <input type="file" @change="uploadFile($event)" class="block w-full text-xs text-slate-600">
                                <div class="flex gap-2">
                                    <input type="url" x-model="attachUrl" placeholder="https://…" class="flex-1 rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
                                    <button type="button" @click="addLink()" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-3 py-2">Lier</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Membres</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="m in card.membres" :key="m.id">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-escm-primary text-xs font-bold text-white overflow-hidden shrink-0" :title="m.name">
                                                <template x-if="m.avatar_url"><img :src="m.avatar_url" :alt="m.name" class="h-full w-full object-cover"></template>
                                                <span x-show="!m.avatar_url" x-text="m.initials"></span>
                                            </span>
                                        </template>
                                        <span x-show="!card.membres.length" class="text-sm text-slate-400">Aucun</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Étiquettes</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="e in card.etiquettes" :key="e.id">
                                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold" :class="e.classes.badge" x-text="e.nom"></span>
                                        </template>
                                        <span x-show="!card.etiquettes.length" class="text-sm text-slate-400">Aucune</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Dates</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="date" x-model="card.date_debut" @change="saveDates()" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
                                    <span class="text-slate-400">→</span>
                                    <input type="date" :value="card.date_fin ? card.date_fin.substring(0,10) : ''" @change="card.date_fin = $event.target.value; saveDates()" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
                                    <span x-show="card.is_overdue" class="inline-flex items-center rounded bg-red-100 text-red-700 text-xs font-semibold px-2 py-1">En retard</span>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Description</p>
                                </div>
                                <textarea
                                    x-model="card.description"
                                    @change="saveField({ description: card.description })"
                                    rows="6"
                                    placeholder="Ajouter une description…"
                                    class="w-full rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary"
                                ></textarea>
                            </div>

                            <template x-for="cl in card.checklists" :key="cl.id">
                                <div class="rounded-lg border border-slate-200 p-3">
                                    <p class="text-sm font-semibold text-slate-800 mb-2" x-text="cl.titre"></p>
                                    <div class="space-y-1.5 mb-2">
                                        <template x-for="item in cl.items" :key="item.id">
                                            <label class="flex items-start gap-2 text-sm text-slate-700 group/item">
                                                <input type="checkbox" class="mt-0.5 rounded border-slate-300 text-escm-primary focus:ring-escm-primary" :checked="item.fait" @change="toggleItem(item)">
                                                <span class="flex-1" :class="item.fait && 'line-through text-slate-400'" x-text="item.titre"></span>
                                                <button type="button" class="opacity-0 group-hover/item:opacity-100 text-slate-400 hover:text-red-600" @click="removeItem(item, cl)">×</button>
                                            </label>
                                        </template>
                                    </div>
                                    <form @submit.prevent="addChecklistItem(cl, $event)" class="flex gap-2">
                                        <input type="text" name="titre" placeholder="Ajouter un élément…" class="flex-1 rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary" required>
                                        <button type="submit" class="rounded-lg bg-slate-800 text-white text-xs font-semibold px-3">Ajouter</button>
                                    </form>
                                </div>
                            </template>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pièces jointes</p>
                                </div>
                                <div class="space-y-2">
                                    <template x-for="p in card.pieces_jointes" :key="p.id">
                                        <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                            <a :href="p.url" target="_blank" class="text-sm text-escm-primary hover:underline truncate" x-text="p.nom"></a>
                                            <button type="button" class="text-slate-400 hover:text-red-600 text-sm" @click="removePiece(p)">Suppr.</button>
                                        </div>
                                    </template>
                                    <p x-show="!card.pieces_jointes.length" class="text-sm text-slate-400">Aucun fichier</p>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2 p-5 bg-slate-50/80 space-y-4">
                            <h4 class="text-sm font-semibold text-slate-900">Commentaires et activité</h4>

                            <form @submit.prevent="addComment()" class="space-y-2">
                                <textarea x-model="newComment" rows="3" placeholder="Écrivez un commentaire…" class="w-full rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary" required></textarea>
                                <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-4 py-2">Envoyer</button>
                            </form>

                            <div class="space-y-4 max-h-[28rem] overflow-y-auto pr-1">
                                <template x-for="c in card.commentaires" :key="'c'+c.id">
                                    <div class="flex gap-2.5">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-escm-primary text-[10px] font-bold text-white overflow-hidden">
                                            <template x-if="c.avatar_url"><img :src="c.avatar_url" :alt="c.user" class="h-full w-full object-cover"></template>
                                            <span x-show="!c.avatar_url" x-text="c.initials"></span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-xs text-slate-500"><span class="font-semibold text-slate-800" x-text="c.user"></span> · <span x-text="c.date"></span></p>
                                            <div class="mt-1 rounded-lg bg-white border border-slate-200 px-3 py-2 text-sm text-slate-700 whitespace-pre-wrap" x-text="c.contenu"></div>
                                        </div>
                                    </div>
                                </template>

                                <template x-for="a in card.activites" :key="'a'+a.id">
                                    <div class="text-xs text-slate-500 pl-10">
                                        <span x-text="a.message"></span>
                                        <span class="text-slate-400"> · </span>
                                        <span x-text="a.date"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function projetBoard() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const routes = {
        store: @json(route('gestion-projet.cartes.store')),
        storeListe: @json(route('gestion-projet.listes.store')),
        updateListe: (id) => @json(url('/gestion-projet/listes')).replace(/\/$/, '') + '/' + id,
        destroyListe: (id) => @json(url('/gestion-projet/listes')).replace(/\/$/, '') + '/' + id,
        show: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id,
        update: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id,
        destroy: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id,
        move: @json(route('gestion-projet.cartes.move')),
        membres: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id + '/membres',
        etiquettes: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id + '/etiquettes',
        storeEtiquette: @json(route('gestion-projet.etiquettes.store')),
        checklists: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id + '/checklists',
        checklistItems: (id) => @json(url('/gestion-projet/checklists')).replace(/\/$/, '') + '/' + id + '/items',
        toggleItem: (id) => @json(url('/gestion-projet/checklist-items')).replace(/\/$/, '') + '/' + id + '/toggle',
        destroyItem: (id) => @json(url('/gestion-projet/checklist-items')).replace(/\/$/, '') + '/' + id,
        commentaires: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id + '/commentaires',
        pieces: (id) => @json(url('/gestion-projet/cartes')).replace(/\/$/, '') + '/' + id + '/pieces-jointes',
        destroyPiece: (id) => @json(url('/gestion-projet/pieces-jointes')).replace(/\/$/, '') + '/' + id,
    };

    return {
        open: false,
        loading: false,
        card: null,
        showMembers: false,
        showLabels: false,
        showAttach: false,
        newComment: '',
        attachUrl: '',
        availableLabels: @json($etiquettes->map->toBoardArray()->values()),

        init() {
            document.querySelectorAll('.sortable-list').forEach((el) => {
                Sortable.create(el, {
                    group: 'projet-kanban',
                    animation: 150,
                    ghostClass: 'opacity-40',
                    draggable: 'article',
                    onEnd: (evt) => this.onMove(evt),
                });
            });
        },

        async request(url, options = {}) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            };
            if (options.json) {
                headers['Content-Type'] = 'application/json';
            }
            const res = await fetch(url, { ...options, headers, body: options.json ? JSON.stringify(options.json) : options.body });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Erreur serveur');
            }
            return res.status === 204 ? {} : res.json();
        },

        async addCard(event, listeId) {
            const form = event.target;
            const titre = form.titre.value.trim();
            if (!titre) return;
            await this.request(routes.store, {
                method: 'POST',
                json: { titre, projet_liste_id: listeId },
            });
            window.location.reload();
        },

        async addListe(event) {
            const nom = event.target.nom.value.trim();
            if (!nom) return;
            await this.request(routes.storeListe, {
                method: 'POST',
                json: { nom },
            });
            window.location.reload();
        },

        async renameListe(id, current) {
            const nom = prompt('Nouveau nom de la liste', current);
            if (!nom || nom.trim() === '' || nom === current) return;
            await this.request(routes.updateListe(id), { method: 'PATCH', json: { nom: nom.trim() } });
            window.location.reload();
        },

        async deleteListe(id) {
            if (!confirm('Supprimer cette liste ? Elle doit être vide.')) return;
            try {
                await this.request(routes.destroyListe(id), { method: 'DELETE' });
                window.location.reload();
            } catch (e) {
                alert(e.message);
            }
        },

        async onMove(evt) {
            const list = evt.to;
            const listeId = parseInt(list.dataset.listeId, 10);
            const orderedIds = [...list.querySelectorAll('article')].map((el) => parseInt(el.dataset.id, 10));
            const carteId = parseInt(evt.item.dataset.id, 10);
            await this.request(routes.move, {
                method: 'POST',
                json: { carte_id: carteId, projet_liste_id: listeId, ordered_ids: orderedIds },
            });
        },

        async openCard(id) {
            this.open = true;
            this.loading = true;
            this.showMembers = false;
            this.showLabels = false;
            this.showAttach = false;
            this.newComment = '';
            try {
                this.card = await this.request(routes.show(id));
            } catch (e) {
                alert(e.message);
                this.open = false;
            } finally {
                this.loading = false;
            }
        },

        closeCard() {
            this.open = false;
            this.card = null;
        },

        async saveField(payload) {
            if (!this.card) return;
            await this.request(routes.update(this.card.id), { method: 'PATCH', json: payload });
            if (payload.projet_liste_id) {
                window.location.reload();
            }
        },

        async saveDates() {
            await this.saveField({
                date_debut: this.card.date_debut || null,
                date_fin: this.card.date_fin ? this.card.date_fin.substring(0, 10) : null,
            });
            const fin = this.card.date_fin ? new Date(this.card.date_fin) : null;
            this.card.is_overdue = !!(fin && fin < new Date() && !this.card.is_done);
        },

        async toggleMember(userId) {
            const ids = this.card.membres.map((m) => m.id);
            const idx = ids.indexOf(userId);
            if (idx >= 0) ids.splice(idx, 1); else ids.push(userId);
            await this.request(routes.membres(this.card.id), { method: 'POST', json: { user_ids: ids } });
            this.card = await this.request(routes.show(this.card.id));
        },

        async toggleLabel(labelId) {
            const ids = this.card.etiquettes.map((e) => e.id);
            const idx = ids.indexOf(labelId);
            if (idx >= 0) ids.splice(idx, 1); else ids.push(labelId);
            await this.request(routes.etiquettes(this.card.id), { method: 'POST', json: { etiquette_ids: ids } });
            this.card = await this.request(routes.show(this.card.id));
        },

        async createLabel(event) {
            const nom = event.target.nom.value.trim();
            const couleur = event.target.couleur.value;
            if (!nom) return;
            const res = await this.request(routes.storeEtiquette, { method: 'POST', json: { nom, couleur } });
            if (res.etiquette) {
                this.availableLabels.push(res.etiquette);
                this.availableLabels.sort((a, b) => a.nom.localeCompare(b.nom, 'fr'));
            }
            event.target.reset();
        },

        async addChecklist() {
            const titre = prompt('Titre de la checklist', 'Checklist');
            if (titre === null) return;
            await this.request(routes.checklists(this.card.id), { method: 'POST', json: { titre: titre || 'Checklist' } });
            this.card = await this.request(routes.show(this.card.id));
        },

        async addChecklistItem(cl, event) {
            const titre = event.target.titre.value.trim();
            if (!titre) return;
            await this.request(routes.checklistItems(cl.id), { method: 'POST', json: { titre } });
            event.target.reset();
            this.card = await this.request(routes.show(this.card.id));
        },

        async toggleItem(item) {
            const res = await this.request(routes.toggleItem(item.id), { method: 'PATCH', json: {} });
            item.fait = res.fait;
        },

        async removeItem(item, cl) {
            await this.request(routes.destroyItem(item.id), { method: 'DELETE' });
            cl.items = cl.items.filter((i) => i.id !== item.id);
        },

        async addComment() {
            if (!this.newComment.trim()) return;
            const res = await this.request(routes.commentaires(this.card.id), {
                method: 'POST',
                json: { contenu: this.newComment },
            });
            this.card.commentaires.unshift(res.commentaire);
            this.newComment = '';
        },

        async uploadFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('fichier', file);
            const res = await fetch(routes.pieces(this.card.id), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            if (!res.ok) { alert('Échec upload'); return; }
            const data = await res.json();
            this.card.pieces_jointes.unshift(data.piece);
            event.target.value = '';
            this.showAttach = false;
        },

        async addLink() {
            if (!this.attachUrl.trim()) return;
            const res = await this.request(routes.pieces(this.card.id), {
                method: 'POST',
                json: { url: this.attachUrl },
            });
            this.card.pieces_jointes.unshift(res.piece);
            this.attachUrl = '';
            this.showAttach = false;
        },

        async removePiece(p) {
            await this.request(routes.destroyPiece(p.id), { method: 'DELETE' });
            this.card.pieces_jointes = this.card.pieces_jointes.filter((x) => x.id !== p.id);
        },

        async confirmDelete() {
            if (!confirm('Supprimer cette carte ?')) return;
            await this.request(routes.destroy(this.card.id), { method: 'DELETE' });
            window.location.reload();
        },
    };
}
</script>
<style>[x-cloak]{display:none!important}</style>
@endpush
