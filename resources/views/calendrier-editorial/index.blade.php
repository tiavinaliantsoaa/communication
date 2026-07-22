@php
    $title = $title ?? 'Calendrier éditorial';
    $subtitle = $subtitle ?? '';
@endphp

@extends('layouts.app')

@section('content')
<div
    class="-m-4 sm:-m-6 lg:-m-8"
    x-data="editorialCalendar(@js([
        'view' => $view,
        'currentDate' => $currentDate,
        'rangeStart' => $rangeStart,
        'rangeEnd' => $rangeEnd,
        'rangeLabel' => $rangeLabel,
        'events' => $events,
        'categories' => $categories,
        'statuts' => $statuts,
        'typesContenu' => $typesContenu,
        'canValidate' => $canValidate,
        'maxVisuels' => $maxVisuels ?? 10,
        'baseUrl' => route('calendrier-editorial'),
        'storeUrl' => route('calendrier-editorial.store'),
        'today' => now()->toDateString(),
        'openCreate' => $errors->any() && old('form_mode') !== 'edit',
        'openEdit' => $errors->any() && old('form_mode') === 'edit',
        'editEventId' => old('event_id'),
        'old' => [
            'titre' => old('titre', ''),
            'categorie' => array_values((array) old('categorie', ['facebook'])),
            'type_contenu' => old('type_contenu', 'FI'),
            'booster' => (bool) old('booster', false),
            'date_debut' => old('date_debut', $currentDate),
            'date_fin' => old('date_fin', ''),
            'statut' => old('statut', 'planifie'),
            'valide' => (bool) old('valide', false),
            'texte_publication' => old('texte_publication', ''),
            'texte_publication_linkedin' => old('texte_publication_linkedin', ''),
        ],
    ]))"
>
    {{-- Toolbar --}}
    <div class="sticky top-[73px] z-20 bg-white border-b border-slate-200 px-3 sm:px-4 py-2.5 flex flex-wrap items-center gap-2 sm:gap-3">
        <div class="flex items-center gap-1">
            <button type="button" @click="navigate(-1)" class="p-1.5 rounded border border-slate-200 text-slate-600 hover:bg-slate-50" title="Précédent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" @click="navigate(1)" class="p-1.5 rounded border border-slate-200 text-slate-600 hover:bg-slate-50" title="Suivant">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button type="button" @click="goToday()" class="ml-1 px-3 py-1.5 text-xs font-semibold rounded border border-slate-200 text-slate-700 hover:bg-slate-50">
                Aujourd'hui
            </button>
        </div>

        <div class="text-sm font-semibold text-slate-800 capitalize min-w-0">
            <span x-text="rangeLabel"></span>
        </div>

        <div class="flex-1"></div>

        <button
            type="button"
            @click="openCreateModal()"
            class="inline-flex items-center gap-1.5 bg-escm-primary hover:bg-escm-primary-dark text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajouter
        </button>

        <div class="flex flex-wrap items-center gap-0.5 bg-slate-100 rounded-lg p-0.5">
            <template x-for="opt in viewOptions" :key="opt.key">
                <button
                    type="button"
                    @click="setView(opt.key)"
                    class="px-2.5 py-1.5 text-[11px] font-semibold rounded-md transition-colors"
                    :class="view === opt.key ? 'bg-white text-escm-primary shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    x-text="opt.label"
                ></button>
            </template>
        </div>
    </div>

    {{-- Légende rapide (toujours visible) --}}
    <div class="bg-white border-b border-slate-200 px-3 sm:px-4 py-2 overflow-x-auto">
        <div class="flex items-center gap-x-4 gap-y-1.5 flex-wrap">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 shrink-0">Légende</span>
            @foreach($categories as $cat)
                <div class="inline-flex items-center gap-1.5 shrink-0">
                    <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                    <span class="text-[11px] text-slate-700 whitespace-nowrap">{{ $cat['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex min-h-[calc(100vh-10rem)]">
        {{-- Left panel --}}
        <aside class="hidden md:flex w-56 lg:w-64 flex-col border-r border-slate-200 bg-white shrink-0">
            {{-- Mini calendar --}}
            <div class="p-3 border-b border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <button type="button" @click="shiftMiniMonth(-1)" class="p-1 rounded hover:bg-slate-100 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="text-xs font-semibold text-slate-800 capitalize" x-text="miniMonthLabel"></span>
                    <button type="button" @click="shiftMiniMonth(1)" class="p-1 rounded hover:bg-slate-100 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-0.5 text-center mb-1">
                    <template x-for="d in ['L','M','M','J','V','S','D']" :key="d">
                        <div class="text-[10px] font-semibold text-slate-400 py-0.5" x-text="d"></div>
                    </template>
                </div>
                <div class="grid grid-cols-7 gap-0.5">
                    <template x-for="cell in miniDays" :key="cell.key">
                        <button
                            type="button"
                            @click="selectDate(cell.date)"
                            class="h-7 text-[11px] rounded transition-colors"
                            :class="{
                                'text-slate-300': !cell.inMonth,
                                'text-slate-700 hover:bg-slate-100': cell.inMonth && !cell.isSelected && !cell.isToday,
                                'bg-escm-primary text-white font-semibold': cell.isSelected,
                                'ring-1 ring-escm-primary text-escm-primary font-semibold': cell.isToday && !cell.isSelected,
                                'bg-blue-50': cell.inRange && !cell.isSelected,
                            }"
                            x-text="cell.day"
                        ></button>
                    </template>
                </div>
            </div>

            {{-- Légende couleurs --}}
            <div class="flex-1 overflow-y-auto p-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Légende</p>
                    <button type="button" @click="toggleAllCategories()" class="text-[10px] text-escm-primary hover:underline" x-text="allVisible ? 'Tout masquer' : 'Tout afficher'"></button>
                </div>
                <input
                    type="search"
                    x-model="categoryFilter"
                    placeholder="Rechercher…"
                    class="w-full mb-2 rounded-md border-slate-200 text-xs focus:border-escm-primary focus:ring-escm-primary"
                >
                <div class="space-y-0.5">
                    @foreach($categories as $cat)
                        <label
                            class="flex items-center gap-2 px-1.5 py-1.5 rounded hover:bg-slate-50 cursor-pointer"
                            x-show="!categoryFilter || '{{ strtolower($cat['label']) }}'.includes(categoryFilter.trim().toLowerCase())"
                        >
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                                :checked="visibleCategories['{{ $cat['key'] }}']"
                                @change="toggleCategory('{{ $cat['key'] }}')"
                            >
                            <span class="inline-block w-3 h-3 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                            <span class="text-xs text-slate-700 truncate">{{ $cat['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </aside>

        {{-- Main calendar --}}
        <div class="flex-1 min-w-0 bg-slate-50 overflow-auto">
            {{-- Day / Week / 2 Weeks / Month grid --}}
            <template x-if="view !== 'list'">
                <div class="min-w-[720px]">
                    {{-- Weekday headers --}}
                    <div class="grid border-b border-slate-200 bg-white sticky top-0 z-10" :style="`grid-template-columns: 28px repeat(${daysPerRow}, minmax(0, 1fr))`">
                        <div class="border-r border-slate-100"></div>
                        <template x-for="(day, i) in headerDays" :key="'h-'+i">
                            <div class="px-2 py-2 text-center border-r border-slate-100 last:border-r-0">
                                <div class="text-[10px] font-semibold uppercase text-slate-400" x-text="day.weekday"></div>
                                <div class="text-sm font-bold" :class="day.isToday ? 'text-escm-primary' : 'text-slate-800'" x-text="day.dayNum"></div>
                            </div>
                        </template>
                    </div>

                    {{-- Weeks --}}
                    <template x-for="(week, wi) in weeks" :key="'w-'+wi">
                        <div class="grid border-b border-slate-200" :style="`grid-template-columns: 28px repeat(${daysPerRow}, minmax(0, 1fr)); min-height: ${cellMinHeight}px`">
                            <div class="border-r border-slate-100 bg-slate-50 flex items-center justify-center">
                                <span class="text-[9px] font-semibold text-slate-400 -rotate-90 whitespace-nowrap" x-text="'S'+week.weekNum"></span>
                            </div>
                            <template x-for="(day, di) in week.days" :key="day.date">
                                <div
                                    class="border-r border-slate-100 last:border-r-0 p-1 align-top group/day cursor-pointer"
                                    :class="day.isToday ? 'bg-blue-50/40' : (day.inMonth === false ? 'bg-slate-50/80' : 'bg-white')"
                                    @click.self="openCreateModal(day.date)"
                                >
                                    <div class="flex items-center justify-between mb-0.5">
                                        <button
                                            type="button"
                                            @click.stop="openCreateModal(day.date)"
                                            class="opacity-0 group-hover/day:opacity-100 p-0.5 rounded text-escm-primary hover:bg-blue-50 transition-opacity"
                                            title="Ajouter un contenu"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                        <span
                                            class="inline-flex items-center justify-center w-6 h-6 text-[11px] font-semibold rounded-full"
                                            :class="day.isToday ? 'bg-escm-primary text-white' : 'text-slate-500'"
                                            x-text="day.dayNum"
                                        ></span>
                                    </div>
                                    <div class="space-y-0.5">
                                        <template x-for="ev in day.events" :key="ev.id + '-' + day.date">
                                            <button
                                                type="button"
                                                @click.stop="selectedEvent = ev"
                                                class="w-full text-left px-1.5 py-0.5 rounded text-[10px] font-medium truncate leading-tight shadow-sm hover:opacity-90 transition-opacity"
                                                :style="`background:${ev.color};color:${ev.text}`"
                                                :title="ev.titre + (ev.type_contenu ? ' [' + ev.type_contenu + ']' : '') + (ev.booster ? ' · Booster' : '')"
                                            >
                                                <span x-show="ev.type_contenu" class="opacity-90" x-text="'[' + ev.type_contenu + '] '"></span>
                                                <span x-text="ev.titre"></span>
                                                <span x-show="ev.booster" class="opacity-90"> · Boost</span>
                                            </button>
                                        </template>
                                        <template x-if="day.moreCount > 0">
                                            <div class="text-[10px] text-slate-500 px-1 font-medium" x-text="'+' + day.moreCount + ' autres'"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- List view --}}
            <template x-if="view === 'list'">
                <div class="p-4 max-w-3xl">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                        <template x-if="visibleEvents.length === 0">
                            <div class="p-8 text-center text-sm text-slate-500">Aucun contenu planifié sur cette période.</div>
                        </template>
                        <template x-for="ev in visibleEvents" :key="'list-'+ev.id">
                            <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50">
                                <button type="button" @click="selectedEvent = ev" class="flex items-start gap-3 min-w-0 flex-1 text-left">
                                    <span class="mt-1 w-3 h-3 rounded-sm shrink-0" :style="`background:${ev.color}`"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-slate-800 truncate" x-text="ev.titre"></div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            <span x-text="formatDate(ev.date_debut)"></span>
                                            <template x-if="ev.date_fin !== ev.date_debut">
                                                <span> → <span x-text="formatDate(ev.date_fin)"></span></span>
                                            </template>
                                            <span class="mx-1">·</span>
                                            <span x-text="ev.label"></span>
                                            <template x-if="ev.type_contenu">
                                                <span> · <span x-text="ev.type_contenu"></span></span>
                                            </template>
                                            <template x-if="ev.booster">
                                                <span> · Booster</span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span class="text-[10px] uppercase font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600" x-text="statutLabel(ev.statut)"></span>
                                        <span x-show="ev.valide" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Validé</span>
                                    </div>
                                </button>
                                <form :action="ev.delete_url" method="POST" @submit="return confirm('Supprimer ce contenu du calendrier ?')" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_date" :value="currentDate">
                                    <input type="hidden" name="return_view" :value="view">
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Event detail modal --}}
    <div
        x-show="selectedEvent"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="if (lightbox.open) { closeLightbox(); return; } if (!showCreate && !showEdit) selectedEvent = null"
    >
        <div class="absolute inset-0 bg-black/40" @click="selectedEvent = null"></div>
        <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg p-5" x-show="selectedEvent" x-transition>
            <template x-if="selectedEvent">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-3.5 h-3.5 rounded-sm shrink-0" :style="`background:${selectedEvent.color}`"></span>
                            <h3 class="text-base font-bold text-slate-900 truncate" x-text="selectedEvent.titre"></h3>
                        </div>
                        <button type="button" @click="selectedEvent = null" class="p-1 rounded hover:bg-slate-100 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="text-slate-500 w-28 shrink-0">Catégorie</dt>
                            <dd class="text-slate-800">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="cat in (selectedEvent.categories || [])" :key="cat.key">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-white"
                                              :style="`background:${cat.color}`">
                                            <span x-text="cat.label"></span>
                                        </span>
                                    </template>
                                </div>
                            </dd>
                        </div>
                        <div class="flex gap-2" x-show="selectedEvent.type_contenu"><dt class="text-slate-500 w-28 shrink-0">Type</dt><dd class="text-slate-800 font-semibold" x-text="selectedEvent.type_contenu"></dd></div>
                        <div class="flex gap-2" x-show="selectedEvent.booster"><dt class="text-slate-500 w-28 shrink-0">Booster</dt><dd class="text-slate-800">Oui</dd></div>
                        <div class="flex gap-2"><dt class="text-slate-500 w-28 shrink-0">Début</dt><dd class="text-slate-800" x-text="formatDate(selectedEvent.date_debut)"></dd></div>
                        <div class="flex gap-2" x-show="selectedEvent.date_fin !== selectedEvent.date_debut || selectedEvent.booster"><dt class="text-slate-500 w-28 shrink-0">Fin</dt><dd class="text-slate-800" x-text="formatDate(selectedEvent.date_fin)"></dd></div>
                        <div class="flex gap-2"><dt class="text-slate-500 w-28 shrink-0">Statut</dt><dd class="text-slate-800" x-text="statutLabel(selectedEvent.statut)"></dd></div>
                        <div class="flex gap-2"><dt class="text-slate-500 w-28 shrink-0">Validé</dt><dd class="text-slate-800" x-text="selectedEvent.valide ? 'Oui' : 'Non'"></dd></div>
                        <div class="flex gap-2" x-show="selectedEvent.texte_publication">
                            <dt class="text-slate-500 w-28 shrink-0">Texte publication</dt>
                            <dd class="text-slate-800 whitespace-pre-wrap" x-text="selectedEvent.texte_publication"></dd>
                        </div>
                        <div class="flex gap-2" x-show="selectedEvent.texte_publication_linkedin">
                            <dt class="text-slate-500 w-28 shrink-0">Texte publication LinkedIn</dt>
                            <dd class="text-slate-800 whitespace-pre-wrap" x-text="selectedEvent.texte_publication_linkedin"></dd>
                        </div>
                        <div class="flex gap-2" x-show="(selectedEvent.visuels && selectedEvent.visuels.length) || selectedEvent.visuel_url">
                            <dt class="text-slate-500 w-28 shrink-0">Visuel</dt>
                            <dd class="flex-1 min-w-0">
                                <div
                                    class="visuel-grid gap-1.5"
                                    :class="visuelGridClass((selectedEvent.visuels && selectedEvent.visuels.length) ? selectedEvent.visuels.length : 1)"
                                >
                                    <template x-for="(v, idx) in (selectedEvent.visuels && selectedEvent.visuels.length ? selectedEvent.visuels : [{ url: selectedEvent.visuel_url, nom: selectedEvent.visuel_nom }])" :key="v.id || idx">
                                        <button
                                            type="button"
                                            class="visuel-thumb group relative overflow-hidden rounded-lg border border-slate-200 bg-slate-50 aspect-square focus:outline-none focus:ring-2 focus:ring-escm-primary/40"
                                            @click="openLightbox(selectedEvent.visuels && selectedEvent.visuels.length ? selectedEvent.visuels : [{ url: selectedEvent.visuel_url, nom: selectedEvent.visuel_nom }], idx)"
                                        >
                                            <img :src="v.url" :alt="v.nom || 'Visuel'" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                        </button>
                                    </template>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1.5">Cliquez une image pour l’agrandir</p>
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" @click="selectedEvent = null" class="px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg">
                            Fermer
                        </button>
                        <button type="button" @click="startEdit(selectedEvent)" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-escm-primary hover:bg-escm-primary-dark rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Modifier
                        </button>
                        <form :action="selectedEvent.delete_url" method="POST" @submit="return confirm('Supprimer ce contenu du calendrier ?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_date" :value="currentDate">
                            <input type="hidden" name="return_view" :value="view">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Create modal --}}
    <div
        x-show="showCreate"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="if (showCreate) showCreate = false"
    >
        <div class="absolute inset-0 bg-black/40" @click="showCreate = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg p-5 max-h-[90vh] overflow-y-auto" x-show="showCreate" x-transition>
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Ajouter un contenu</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Planifier un élément dans le calendrier éditorial</p>
                </div>
                <button type="button" @click="showCreate = false" class="p-1 rounded hover:bg-slate-100 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" :action="storeUrl" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="return_date" :value="currentDate">
                <input type="hidden" name="return_view" :value="view">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
                    <input type="text" name="titre" x-model="form.titre" required maxlength="255"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
                           placeholder="Ex. Post Facebook MBA">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Catégories <span class="text-red-500">*</span></label>
                    <p class="text-xs text-slate-500 mb-2">Vous pouvez en sélectionner plusieurs.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        @foreach($categories as $cat)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 cursor-pointer hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    name="categorie[]"
                                    value="{{ $cat['key'] }}"
                                    :checked="form.categorie.includes('{{ $cat['key'] }}')"
                                    @change="toggleFormCategory('{{ $cat['key'] }}')"
                                    class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                                >
                                <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                <span>{{ $cat['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div x-show="form.categorie.length" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type de contenu</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="radio" name="type_contenu" value="FI" x-model="form.type_contenu" @change="onCategoryOrTypeChange()" class="text-escm-primary focus:ring-escm-primary border-slate-300">
                            <span class="font-semibold">FI</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="radio" name="type_contenu" value="FP" x-model="form.type_contenu" @change="onCategoryOrTypeChange()" class="text-escm-primary focus:ring-escm-primary border-slate-300">
                            <span class="font-semibold">FP</span>
                        </label>
                    </div>
                </div>

                <div x-show="isFacebookFi" x-cloak class="rounded-lg border border-blue-100 bg-blue-50/60 p-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800 cursor-pointer">
                        <input type="checkbox" name="booster" value="1" x-model="form.booster" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                        Booster
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" x-text="form.booster && isFacebookFi ? 'Date de début' : 'Date de publication'"></label>
                        <input type="date" name="date_debut" x-model="form.date_debut" required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                    </div>
                    <div x-show="isFacebookFi && form.booster" x-cloak>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date de fin</label>
                        <input type="date" name="date_fin" x-model="form.date_fin" :required="isFacebookFi && form.booster"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                    <select name="statut" x-model="form.statut" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                        @foreach($statuts as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Texte publication <span class="text-red-500">*</span>
                    </label>
                    <textarea name="texte_publication" x-model="form.texte_publication" rows="3" maxlength="5000" required
                              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
                              placeholder="Texte de la publication…"></textarea>
                </div>

                <div x-show="hasFormLinkedIn" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Texte publication LinkedIn <span class="text-red-500">*</span>
                    </label>
                    <textarea name="texte_publication_linkedin" x-model="form.texte_publication_linkedin" rows="3" maxlength="5000"
                              :required="hasFormLinkedIn"
                              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
                              placeholder="Texte spécifique pour LinkedIn…"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Visuels <span class="text-slate-400 font-normal">(max {{ $maxVisuels ?? 10 }})</span></label>
                    <input type="file" name="visuels[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                           @change="onCreateVisuelsChange($event)"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-escm-primary file:text-white hover:file:bg-escm-primary-dark">
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, WEBP ou GIF — max 5 Mo chacun, jusqu’à {{ $maxVisuels ?? 10 }} images</p>
                    <div x-show="createPreviews.length" x-cloak class="mt-2 visuel-grid gap-1.5" :class="visuelGridClass(createPreviews.length)">
                        <template x-for="(p, idx) in createPreviews" :key="idx">
                            <button type="button" class="visuel-thumb aspect-square overflow-hidden rounded-lg border border-slate-200" @click="openLightbox(createPreviews, idx)">
                                <img :src="p.url" :alt="p.nom" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>
                </div>

                @if($canValidate)
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800 cursor-pointer">
                    <input type="checkbox" name="valide" value="1" x-model="form.valide" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                    Validé
                </label>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showCreate = false" class="px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-escm-primary hover:bg-escm-primary-dark rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit modal --}}
    <div
        x-show="showEdit"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="if (showEdit) showEdit = false"
    >
        <div class="absolute inset-0 bg-black/40" @click="showEdit = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg p-5 max-h-[90vh] overflow-y-auto" x-show="showEdit" x-transition>
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Modifier le contenu</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Mettre à jour l'élément du calendrier éditorial</p>
                </div>
                <button type="button" @click="showEdit = false" class="p-1 rounded hover:bg-slate-100 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700" x-show="editErrors">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" :action="editingEvent ? editingEvent.update_url : ''" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_mode" value="edit">
                <input type="hidden" name="event_id" :value="editingEvent ? editingEvent.id : ''">
                <input type="hidden" name="return_date" :value="currentDate">
                <input type="hidden" name="return_view" :value="view">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
                    <input type="text" name="titre" x-model="editForm.titre" required maxlength="255"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Catégories <span class="text-red-500">*</span></label>
                    <p class="text-xs text-slate-500 mb-2">Vous pouvez en sélectionner plusieurs.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        @foreach($categories as $cat)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-sm text-slate-700 cursor-pointer hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    name="categorie[]"
                                    value="{{ $cat['key'] }}"
                                    :checked="editForm.categorie.includes('{{ $cat['key'] }}')"
                                    @change="toggleEditCategory('{{ $cat['key'] }}')"
                                    class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                                >
                                <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                <span>{{ $cat['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div x-show="editForm.categorie.length" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type de contenu</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="radio" name="type_contenu" value="FI" x-model="editForm.type_contenu" @change="onEditCategoryOrTypeChange()" class="text-escm-primary focus:ring-escm-primary border-slate-300">
                            <span class="font-semibold">FI</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="radio" name="type_contenu" value="FP" x-model="editForm.type_contenu" @change="onEditCategoryOrTypeChange()" class="text-escm-primary focus:ring-escm-primary border-slate-300">
                            <span class="font-semibold">FP</span>
                        </label>
                    </div>
                </div>

                <div x-show="isEditFacebookFi" x-cloak class="rounded-lg border border-blue-100 bg-blue-50/60 p-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800 cursor-pointer">
                        <input type="checkbox" name="booster" value="1" x-model="editForm.booster" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                        Booster
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" x-text="editForm.booster && isEditFacebookFi ? 'Date de début' : 'Date de publication'"></label>
                        <input type="date" name="date_debut" x-model="editForm.date_debut" required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                    </div>
                    <div x-show="isEditFacebookFi && editForm.booster" x-cloak>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date de fin</label>
                        <input type="date" name="date_fin" x-model="editForm.date_fin" :required="isEditFacebookFi && editForm.booster"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                    <select name="statut" x-model="editForm.statut" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                        @foreach($statuts as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Texte publication <span class="text-red-500">*</span>
                    </label>
                    <textarea name="texte_publication" x-model="editForm.texte_publication" rows="3" maxlength="5000" required
                              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"></textarea>
                </div>

                <div x-show="hasEditLinkedIn" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Texte publication LinkedIn <span class="text-red-500">*</span>
                    </label>
                    <textarea name="texte_publication_linkedin" x-model="editForm.texte_publication_linkedin" rows="3" maxlength="5000"
                              :required="hasEditLinkedIn"
                              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
                              placeholder="Texte spécifique pour LinkedIn…"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Visuels <span class="text-slate-400 font-normal">(max {{ $maxVisuels ?? 10 }})</span></label>
                    <template x-for="id in editForm.remove_visuel_ids" :key="'rm'+id">
                        <input type="hidden" name="remove_visuel_ids[]" :value="id">
                    </template>
                    <div x-show="editingEvent && existingEditVisuels.length" x-cloak class="mb-2">
                        <div class="visuel-grid gap-1.5" :class="visuelGridClass(existingEditVisuels.length)">
                            <template x-for="(v, idx) in existingEditVisuels" :key="v.id">
                                <div class="relative group" :class="editForm.remove_visuel_ids.includes(v.id) && 'opacity-40'">
                                    <button type="button" class="visuel-thumb block w-full aspect-square overflow-hidden rounded-lg border border-slate-200" @click="openLightbox(existingEditVisuels, idx)">
                                        <img :src="v.url" :alt="v.nom || 'Visuel'" class="h-full w-full object-cover">
                                    </button>
                                    <button
                                        type="button"
                                        class="absolute top-1 right-1 rounded bg-white/95 border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold shadow-sm"
                                        :class="editForm.remove_visuel_ids.includes(v.id) ? 'text-emerald-700' : 'text-red-600'"
                                        @click="toggleRemoveVisuel(v.id)"
                                        x-text="editForm.remove_visuel_ids.includes(v.id) ? 'Annuler' : 'Suppr.'"
                                    ></button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <input type="file" name="visuels[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                           @change="onEditVisuelsChange($event)"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-escm-primary file:text-white hover:file:bg-escm-primary-dark">
                    <p class="mt-1 text-xs text-slate-500" x-text="`Ajouter des images (reste ${Math.max(0, maxVisuels - keptEditVisuels.length)} place(s)) — JPG/PNG/WEBP/GIF, max 5 Mo chacune`"></p>
                    <div x-show="editPreviews.length" x-cloak class="mt-2 visuel-grid gap-1.5" :class="visuelGridClass(editPreviews.length)">
                        <template x-for="(p, idx) in editPreviews" :key="'new'+idx">
                            <button type="button" class="visuel-thumb aspect-square overflow-hidden rounded-lg border border-dashed border-escm-primary/40" @click="openLightbox(editPreviews, idx)">
                                <img :src="p.url" :alt="p.nom" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>
                </div>

                @if($canValidate)
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800 cursor-pointer">
                    <input type="checkbox" name="valide" value="1" x-model="editForm.valide" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                    Validé
                </label>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showEdit = false" class="px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-escm-primary hover:bg-escm-primary-dark rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Lightbox visuels --}}
    <div
        x-show="lightbox.open"
        x-cloak
        class="fixed inset-0 z-[100] flex flex-col bg-black/85"
        @keydown.escape.window="if (lightbox.open) closeLightbox()"
        @keydown.arrow-left.window="if (lightbox.open) lightboxPrev()"
        @keydown.arrow-right.window="if (lightbox.open) lightboxNext()"
    >
        {{-- Barre du haut : toujours visible --}}
        <div class="relative z-20 flex items-center gap-3 px-4 py-3 bg-slate-950/90 border-b border-white/10 shrink-0">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-white truncate" x-text="lightbox.items[lightbox.index]?.nom || 'Visuel'"></p>
                <p class="text-xs text-white/55" x-show="lightbox.items.length > 1" x-text="(lightbox.index + 1) + ' / ' + lightbox.items.length"></p>
            </div>
            <a
                x-show="lightbox.items[lightbox.index]"
                :href="lightbox.items[lightbox.index]?.url"
                :download="lightbox.items[lightbox.index]?.nom || 'visuel'"
                class="inline-flex items-center gap-1.5 shrink-0 rounded-lg bg-white/10 hover:bg-white/20 px-3 py-2 text-xs font-semibold text-white transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Télécharger
            </a>
            <button type="button" @click="closeLightbox()" class="shrink-0 p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/10" title="Fermer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Zone image --}}
        <div class="relative flex-1 min-h-0 flex items-center justify-center px-12 sm:px-16 py-4" @click.self="closeLightbox()">
            <button
                type="button"
                x-show="lightbox.items.length > 1"
                @click.stop="lightboxPrev()"
                class="absolute left-2 sm:left-4 z-10 p-2.5 rounded-full bg-black/50 text-white hover:bg-black/70 border border-white/10"
                title="Précédent"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <template x-for="item in (lightbox.items[lightbox.index] ? [lightbox.items[lightbox.index]] : [])" :key="lightbox.index + '-' + (item.url || '')">
                <img
                    :src="item.url"
                    :alt="item.nom || 'Visuel'"
                    class="lightbox-img max-h-full max-w-full w-auto h-auto object-contain rounded-lg shadow-2xl"
                    @click.stop
                >
            </template>

            <button
                type="button"
                x-show="lightbox.items.length > 1"
                @click.stop="lightboxNext()"
                class="absolute right-2 sm:right-4 z-10 p-2.5 rounded-full bg-black/50 text-white hover:bg-black/70 border border-white/10"
                title="Suivant"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .visuel-grid { display: grid; }
    .visuel-grid.cols-1 { grid-template-columns: minmax(0, 10rem); }
    .visuel-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .visuel-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .visuel-thumb { cursor: zoom-in; }
    .lightbox-img {
        animation: visuelZoomIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes visuelZoomIn {
        from { opacity: 0; transform: scale(0.92); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endsection

@push('scripts')
<script>
function editorialCalendar(config) {
    const parseDate = (s) => {
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    };
    const fmt = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };
    const addDays = (date, n) => {
        const d = new Date(date);
        d.setDate(d.getDate() + n);
        return d;
    };
    const startOfWeek = (date) => {
        const d = new Date(date);
        const day = d.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        return addDays(d, diff);
    };
    const weekNumber = (date) => {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    };
    const frWeekdays = ['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'];
    const frMonths = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];

    return {
        view: config.view,
        currentDate: config.currentDate,
        rangeStart: config.rangeStart,
        rangeEnd: config.rangeEnd,
        rangeLabel: config.rangeLabel,
        events: config.events,
        categories: config.categories,
        statuts: config.statuts,
        typesContenu: config.typesContenu,
        canValidate: !!config.canValidate,
        baseUrl: config.baseUrl,
        storeUrl: config.storeUrl,
        today: config.today,
        categoryFilter: '',
        selectedEvent: null,
        showCreate: !!config.openCreate,
        showEdit: false,
        editingEvent: null,
        editErrors: !!config.openEdit,
        form: {
            titre: config.old.titre || '',
            categorie: Array.isArray(config.old.categorie) ? config.old.categorie : ['facebook'],
            type_contenu: config.old.type_contenu || 'FI',
            booster: !!config.old.booster,
            date_debut: config.old.date_debut || config.currentDate,
            date_fin: config.old.date_fin || '',
            statut: config.old.statut || 'planifie',
            valide: !!config.old.valide,
            texte_publication: config.old.texte_publication || '',
            texte_publication_linkedin: config.old.texte_publication_linkedin || '',
        },
        editForm: {
            titre: '',
            categorie: ['facebook'],
            type_contenu: 'FI',
            booster: false,
            date_debut: config.currentDate,
            date_fin: '',
            statut: 'planifie',
            valide: false,
            texte_publication: '',
            texte_publication_linkedin: '',
            remove_visuel_ids: [],
        },
        maxVisuels: config.maxVisuels || 10,
        createPreviews: [],
        editPreviews: [],
        lightbox: { open: false, items: [], index: 0 },
        miniMonth: parseDate(config.currentDate).getMonth(),
        miniYear: parseDate(config.currentDate).getFullYear(),
        visibleCategories: Object.fromEntries(config.categories.map(c => [c.key, true])),
        viewOptions: [
            { key: 'day', label: 'Jour' },
            { key: 'week', label: 'Semaine' },
            { key: '2weeks', label: '2 semaines' },
            { key: 'month', label: 'Mois' },
            { key: 'list', label: 'Liste' },
        ],

        get isFacebookFi() {
            return (this.form.categorie || []).includes('facebook') && this.form.type_contenu === 'FI';
        },

        get isEditFacebookFi() {
            return (this.editForm.categorie || []).includes('facebook') && this.editForm.type_contenu === 'FI';
        },

        get hasFormLinkedIn() {
            return (this.form.categorie || []).includes('linkedin');
        },

        get hasEditLinkedIn() {
            return (this.editForm.categorie || []).includes('linkedin');
        },

        get existingEditVisuels() {
            if (!this.editingEvent) return [];
            if (this.editingEvent.visuels && this.editingEvent.visuels.length) {
                return this.editingEvent.visuels;
            }
            if (this.editingEvent.visuel_url) {
                return [{ id: 'legacy', url: this.editingEvent.visuel_url, nom: this.editingEvent.visuel_nom }];
            }
            return [];
        },

        get keptEditVisuels() {
            const removed = this.editForm.remove_visuel_ids || [];
            return this.existingEditVisuels.filter(v => !removed.includes(v.id));
        },

        visuelGridClass(count) {
            if (count <= 1) return 'cols-1';
            if (count <= 4) return 'cols-2';
            return 'cols-3';
        },

        openLightbox(items, index) {
            this.lightbox = {
                open: true,
                items: (items || []).filter(Boolean),
                index: Math.max(0, index || 0),
            };
        },

        closeLightbox() {
            this.lightbox.open = false;
        },

        lightboxPrev() {
            if (!this.lightbox.items.length) return;
            this.lightbox.index = (this.lightbox.index - 1 + this.lightbox.items.length) % this.lightbox.items.length;
        },

        lightboxNext() {
            if (!this.lightbox.items.length) return;
            this.lightbox.index = (this.lightbox.index + 1) % this.lightbox.items.length;
        },

        toggleRemoveVisuel(id) {
            const list = this.editForm.remove_visuel_ids || [];
            const idx = list.indexOf(id);
            if (idx >= 0) list.splice(idx, 1);
            else list.push(id);
            this.editForm.remove_visuel_ids = [...list];
        },

        onCreateVisuelsChange(event) {
            this.createPreviews = this.buildPreviews(event.target.files, this.maxVisuels);
            if (event.target.files.length > this.maxVisuels) {
                alert('Maximum ' + this.maxVisuels + ' images.');
            }
        },

        onEditVisuelsChange(event) {
            const remaining = Math.max(0, this.maxVisuels - this.keptEditVisuels.length);
            this.editPreviews = this.buildPreviews(event.target.files, remaining);
            if (event.target.files.length > remaining) {
                alert('Il reste ' + remaining + ' place(s) (max ' + this.maxVisuels + ' images au total).');
            }
        },

        buildPreviews(fileList, max) {
            const files = Array.from(fileList || []).slice(0, max);
            const maxBytes = 5 * 1024 * 1024;
            return files.map((file) => {
                if (file.size > maxBytes) {
                    alert(file.name + ' dépasse 5 Mo et peut être refusé à l’envoi.');
                }
                return { url: URL.createObjectURL(file), nom: file.name };
            });
        },

        init() {
            if (config.openEdit && config.editEventId) {
                const ev = this.events.find(e => String(e.id) === String(config.editEventId));
                if (ev) {
                    this.editingEvent = ev;
                }
                const oldCats = Array.isArray(config.old.categorie)
                    ? config.old.categorie
                    : (config.old.categorie ? [config.old.categorie] : ['facebook']);
                this.editForm = {
                    titre: config.old.titre || '',
                    categorie: oldCats,
                    type_contenu: config.old.type_contenu || 'FI',
                    booster: !!config.old.booster,
                    date_debut: config.old.date_debut || this.currentDate,
                    date_fin: config.old.date_fin || '',
                    statut: config.old.statut || 'planifie',
                    valide: !!config.old.valide,
                    texte_publication: config.old.texte_publication || '',
                    texte_publication_linkedin: config.old.texte_publication_linkedin || '',
                    remove_visuel_ids: [],
                };
                this.showEdit = true;
            }
        },

        get allVisible() {
            return this.categories.every(c => this.visibleCategories[c.key]);
        },

        get filteredCategories() {
            const q = this.categoryFilter.trim().toLowerCase();
            if (!q) return this.categories;
            return this.categories.filter(c =>
                c.label.toLowerCase().includes(q) || (c.color_name || '').toLowerCase().includes(q)
            );
        },

        get visibleEvents() {
            return this.events.filter(e => {
                const cats = Array.isArray(e.categorie) ? e.categorie : [e.categorie];
                return cats.some(key => this.visibleCategories[key]);
            });
        },

        get daysPerRow() {
            return this.view === 'day' ? 1 : 7;
        },

        get cellMinHeight() {
            if (this.view === 'month') return 110;
            if (this.view === 'day') return 420;
            return 220;
        },

        get maxEventsPerCell() {
            return this.view === 'month' ? 3 : 8;
        },

        get miniMonthLabel() {
            return `${frMonths[this.miniMonth]} ${this.miniYear}`;
        },

        get miniDays() {
            const first = new Date(this.miniYear, this.miniMonth, 1);
            const start = startOfWeek(first);
            const days = [];
            const rangeStart = parseDate(this.rangeStart);
            const rangeEnd = parseDate(this.rangeEnd);
            for (let i = 0; i < 42; i++) {
                const d = addDays(start, i);
                const dateStr = fmt(d);
                const t = d.getTime();
                days.push({
                    key: dateStr,
                    date: dateStr,
                    day: d.getDate(),
                    inMonth: d.getMonth() === this.miniMonth,
                    isToday: dateStr === this.today,
                    isSelected: dateStr === this.currentDate,
                    inRange: t >= rangeStart.getTime() && t <= rangeEnd.getTime(),
                });
            }
            return days;
        },

        get calendarDays() {
            const start = parseDate(this.rangeStart);
            const end = parseDate(this.rangeEnd);
            const days = [];
            let cursor = new Date(start);
            while (cursor <= end) {
                days.push(this.buildDay(cursor));
                cursor = addDays(cursor, 1);
            }
            return days;
        },

        get headerDays() {
            if (this.view === 'day') return this.calendarDays;
            return this.calendarDays.slice(0, 7);
        },

        get weeks() {
            const days = this.calendarDays;
            if (this.view === 'day') {
                return [{ weekNum: weekNumber(parseDate(days[0].date)), days }];
            }
            const weeks = [];
            for (let i = 0; i < days.length; i += 7) {
                const chunk = days.slice(i, i + 7);
                weeks.push({
                    weekNum: weekNumber(parseDate(chunk[0].date)),
                    days: chunk,
                });
            }
            return weeks;
        },

        buildDay(date) {
            const dateStr = fmt(date);
            const monthRef = parseDate(this.currentDate);
            const all = this.visibleEvents.filter(e => e.date_debut <= dateStr && e.date_fin >= dateStr);
            const max = this.maxEventsPerCell;
            return {
                date: dateStr,
                dayNum: date.getDate(),
                weekday: frWeekdays[(date.getDay() + 6) % 7],
                isToday: dateStr === this.today,
                inMonth: date.getMonth() === monthRef.getMonth(),
                events: all.slice(0, max),
                moreCount: Math.max(0, all.length - max),
            };
        },

        formatDate(dateStr) {
            const d = parseDate(dateStr);
            return `${d.getDate()} ${frMonths[d.getMonth()]} ${d.getFullYear()}`;
        },

        statutLabel(key) {
            return this.statuts[key] || key;
        },

        openCreateModal(dateStr = null) {
            this.selectedEvent = null;
            this.form = {
                titre: '',
                categorie: ['facebook'],
                type_contenu: 'FI',
                booster: false,
                date_debut: dateStr || this.currentDate,
                date_fin: '',
                statut: 'planifie',
                valide: false,
                texte_publication: '',
                texte_publication_linkedin: '',
            };
            this.showCreate = true;
        },

        toggleFormCategory(key) {
            const list = this.form.categorie || [];
            if (list.includes(key)) {
                this.form.categorie = list.filter(k => k !== key);
            } else {
                this.form.categorie = [...list, key];
            }
            this.onCategoryOrTypeChange();
        },

        toggleEditCategory(key) {
            const list = this.editForm.categorie || [];
            if (list.includes(key)) {
                this.editForm.categorie = list.filter(k => k !== key);
            } else {
                this.editForm.categorie = [...list, key];
            }
            this.onEditCategoryOrTypeChange();
        },

        onCategoryOrTypeChange() {
            if (!((this.form.categorie || []).includes('facebook') && this.form.type_contenu === 'FI')) {
                this.form.booster = false;
                this.form.date_fin = '';
            }
        },

        startEdit(ev) {
            if (!ev) return;
            this.editingEvent = ev;
            this.editErrors = false;
            const cats = Array.isArray(ev.categorie)
                ? ev.categorie
                : (ev.categorie ? [ev.categorie] : ['facebook']);
            this.editForm = {
                titre: ev.titre || '',
                categorie: [...cats],
                type_contenu: ev.type_contenu || 'FI',
                booster: !!ev.booster,
                date_debut: ev.date_debut || this.currentDate,
                date_fin: (ev.date_fin && ev.date_fin !== ev.date_debut) ? ev.date_fin : '',
                statut: ev.statut || 'planifie',
                valide: !!ev.valide,
                texte_publication: ev.texte_publication || '',
                texte_publication_linkedin: ev.texte_publication_linkedin || '',
                remove_visuel_ids: [],
            };
            this.editPreviews = [];
            this.selectedEvent = null;
            this.showCreate = false;
            this.showEdit = true;
        },

        onEditCategoryOrTypeChange() {
            if (!((this.editForm.categorie || []).includes('facebook') && this.editForm.type_contenu === 'FI')) {
                this.editForm.booster = false;
                this.editForm.date_fin = '';
            }
        },

        toggleCategory(key) {
            this.visibleCategories[key] = !this.visibleCategories[key];
        },

        toggleAllCategories() {
            const next = !this.allVisible;
            this.categories.forEach(c => { this.visibleCategories[c.key] = next; });
        },

        shiftMiniMonth(delta) {
            let m = this.miniMonth + delta;
            let y = this.miniYear;
            if (m < 0) { m = 11; y--; }
            if (m > 11) { m = 0; y++; }
            this.miniMonth = m;
            this.miniYear = y;
        },

        navigate(dir) {
            const d = parseDate(this.currentDate);
            let next;
            if (this.view === 'day') next = addDays(d, dir);
            else if (this.view === 'week') next = addDays(d, dir * 7);
            else if (this.view === '2weeks') next = addDays(d, dir * 14);
            else next = new Date(d.getFullYear(), d.getMonth() + dir, 1);
            this.reload(fmt(next), this.view);
        },

        goToday() {
            this.reload(this.today, this.view);
        },

        selectDate(dateStr) {
            this.reload(dateStr, this.view);
        },

        setView(view) {
            this.reload(this.currentDate, view);
        },

        reload(date, view) {
            const url = new URL(this.baseUrl, window.location.origin);
            url.searchParams.set('date', date);
            url.searchParams.set('view', view);
            window.location.href = url.toString();
        },
    };
}
</script>
@endpush
