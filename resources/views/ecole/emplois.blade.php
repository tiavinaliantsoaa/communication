@php
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary';
@endphp

@extends('layouts.app')

@section('content')
@php $lectureParent = auth()->user()?->isParent(); @endphp
<div class="grid grid-cols-1 {{ $lectureParent ? '' : 'xl:grid-cols-3' }} gap-6" x-data="calendrierEmploi(window.calendrierBoot)" x-init="dessiner()">
    @unless($lectureParent)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Ajouter un créneau</h3>
        @if($groupes->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord un groupe.</p>
        @elseif($matieres->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord une matière.</p>
            <a href="{{ route('ecole.matieres.index') }}" class="mt-3 inline-flex text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Créer une matière</a>
        @elseif($enseignants->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord un enseignant.</p>
            <a href="{{ route('ecole.enseignants.index') }}" class="mt-3 inline-flex text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Créer un enseignant</a>
        @else
            <form method="POST" action="{{ route('ecole.emplois.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Groupe</label>
                    <select name="ecole_groupe_id" class="{{ $input }}" required x-model="groupeId" @change="dessiner()">
                        @foreach($groupes as $ligne)
                            <option value="{{ $ligne->id }}" @selected((int) old('ecole_groupe_id', $groupe?->id) === (int) $ligne->id)>{{ $ligne->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                    <input type="date" name="date" x-model="date" @change="suivreDate()" required class="{{ $input }}">
                    @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Début</label>
                        <input type="time" name="heure_debut" x-model="debut" @input="dessiner()" min="08:00" max="17:00" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Fin</label>
                        <input type="time" name="heure_fin" x-model="fin" @input="dessiner()" min="08:00" max="17:00" required class="{{ $input }}">
                    </div>
                </div>
                @error('heure_debut')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Matière</label>
                    <select name="ecole_matiere_id" class="{{ $input }}" required>
                        <option value="">Choisir une matière</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}" @selected((int) old('ecole_matiere_id') === (int) $matiere->id)>{{ $matiere->nom }}</option>
                        @endforeach
                    </select>
                    @error('ecole_matiere_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Enseignant</label>
                    <select name="ecole_enseignant_id" class="{{ $input }}" required x-model="enseignantId" @change="dessiner()">
                        <option value="">Choisir un enseignant</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected((int) $enseignantId === (int) $enseignant->id)>{{ $enseignant->nom_complet }}</option>
                        @endforeach
                    </select>
                    @error('ecole_enseignant_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Salle</label>
                    <select name="ecole_salle_id" class="{{ $input }}" x-model="salleId" @change="dessiner()">
                        <option value="">Aucune salle</option>
                        @foreach($salles as $salle)
                            <option value="{{ $salle->id }}" @selected((int) $salleId === (int) $salle->id)>{{ $salle->nom }} — {{ $salle->capacite }} {{ $salle->capacite > 1 ? 'étudiants' : 'étudiant' }}</option>
                        @endforeach
                    </select>
                    @error('ecole_salle_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @if($salles->isEmpty())
                        <a href="{{ route('ecole.parametres.index') }}" class="mt-2 inline-flex text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Créer une salle de classe</a>
                    @endif
                </div>
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg disabled:opacity-60" :disabled="conflit">Enregistrer</button>
                <p x-show="conflit" x-cloak class="text-sm text-red-600">Ce créneau se superpose à un cours déjà prévu. Un groupe, un enseignant et une salle ne peuvent pas avoir deux cours en même temps.</p>
                <p class="text-xs text-slate-500">Ce créneau concerne uniquement cette date, entre 08h et 17h. La salle est refusée si le groupe compte plus d’élèves que sa capacité.</p>
            </form>
        @endif
    </div>
    @endunless

    @php
        $teintes = [
            'groupe' => ['bg' => '#dbeafe', 'border' => '#93c5fd', 'text' => '#1e3a8a'],
            'enseignant' => ['bg' => '#d1fae5', 'border' => '#6ee7b7', 'text' => '#065f46'],
            'salle' => ['bg' => '#ede9fe', 'border' => '#c4b5fd', 'text' => '#5b21b6'],
            'apercu' => ['bg' => '#fff7ed', 'border' => '#fdba74', 'text' => '#9a3412'],
        ];
    @endphp
    <div class="{{ $lectureParent ? '' : 'xl:col-span-2' }} bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Disponibilités — <span x-text="nomGroupe()">{{ $groupe?->nom }}</span></h3>
                <p class="text-xs text-slate-500 mt-0.5" x-text="label">{{ $semaineLabel }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $prevUrl }}" @click.prevent="decaler(-1)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semaine précédente">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <a href="{{ $nextUrl }}" @click.prevent="decaler(1)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semaine suivante">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @if($groupes->isNotEmpty())
                    <label class="sr-only" for="filtre-groupe">Groupe</label>
                    <select id="filtre-groupe" x-model="groupeId" @change="dessiner()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                        @foreach($groupes as $ligne)
                            <option value="{{ $ligne->id }}">{{ $ligne->nom }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
        <div class="px-5 py-3 flex flex-wrap gap-3 text-[11px] font-medium text-slate-600">
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#93c5fd;"></span>Groupe</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#6ee7b7;"></span>Enseignant</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#c4b5fd;"></span>Salle</span>
            @unless($lectureParent)
                <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#fdba74;"></span>Nouveau créneau</span>
            @endunless
        </div>
        <p x-show="conflit" x-cloak class="px-5 pb-3 text-sm font-medium" style="color:#b45309;">Ce créneau se superpose à un cours déjà prévu. Il ne peut pas être enregistré.</p>
        <p x-show="vide" x-cloak class="px-5 pb-3 text-sm text-slate-500">Aucun emploi du temps pour le moment.</p>
        <div class="overflow-x-auto" x-ref="grille">
            <div style="min-width: 920px;">
                <div style="display:grid; grid-template-columns: 72px repeat(5, minmax(0, 1fr));">
                    <div class="px-2 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400 leading-tight">Heure</div>
                    @foreach($columns as $column)
                        <div class="px-3 py-2 text-white" style="background: {{ !empty($column['isSelected']) ? '#1d4ed8' : '#1f2937' }};">
                            <div class="text-[11px] font-medium uppercase tracking-wider" style="color:#cbd5e1;">{{ $column['label'] }}</div>
                            <div class="text-xl font-bold leading-none mt-0.5">{{ $column['number'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="relative" style="height: 576px;">
                    <div style="display:grid; grid-template-columns: 72px repeat(5, minmax(0, 1fr)); height: 100%;">
                        <div class="relative border-r border-slate-200 bg-white">
                            @foreach($hours as $hour)
                                @php $shift = $loop->first ? '0' : ($loop->last ? '-100%' : '-50%'); @endphp
                                <span class="absolute right-2 text-[11px] font-medium text-slate-400" style="top: {{ $hour['top'] }}%; transform: translateY({{ $shift }});">{{ $hour['label'] }}</span>
                            @endforeach
                        </div>
                        @foreach($columns as $column)
                            <div class="relative border-l border-slate-100" style="background: {{ !empty($column['isSelected']) ? '#eff6ff' : (!empty($column['isToday']) ? '#fffbeb' : '#fff') }};">
                                @foreach($hours as $hour)
                                    @if(! $loop->first)
                                        <div class="absolute left-0 right-0" style="top: {{ $hour['top'] }}%; border-top: 1px dashed #e2e8f0;"></div>
                                    @endif
                                @endforeach
                                @foreach($column['events'] as $event)
                                    @php
                                        $teinte = $teintes[$event['kind']] ?? $teintes['groupe'];
                                        $top = number_format($event['top'], 2, '.', '');
                                        $height = number_format($event['height'], 2, '.', '');
                                        $left = number_format($event['left'], 2, '.', '');
                                        $width = number_format($event['width'], 2, '.', '');
                                    @endphp
                                    <div class="absolute overflow-hidden rounded-md px-2 py-1" style="top: {{ $top }}%; height: {{ $height }}%; left: calc({{ $left }}% + 4px); width: calc({{ $width }}% - 8px); background: {{ $teinte['bg'] }}; border: 1px {{ $event['kind'] === 'apercu' ? 'dashed' : 'solid' }} {{ $teinte['border'] }}; z-index: 2;">
                                        <p class="text-[11px] font-bold leading-tight truncate" style="color: {{ $teinte['text'] }};">{{ $event['titre'] }}</p>
                                        @if($event['ligne'] !== '')
                                            <p class="text-[10px] leading-tight truncate" style="color: {{ $teinte['text'] }};">{{ $event['ligne'] }}</p>
                                        @endif
                                        @if($event['meta'] !== '')
                                            <p class="text-[10px] leading-tight truncate" style="color:#475569;">{{ $event['meta'] }}</p>
                                        @endif
                                        <p class="text-[10px] leading-tight truncate" style="color:#64748b;">{{ $event['tags'] }}</p>
                                        @if(!empty($event['supprimable']) && ! $lectureParent)
                                            <form method="POST" action="{{ route('ecole.emplois.destroy', $event['id']) }}" onsubmit="return confirm('Retirer ce créneau ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-[10px] font-semibold" style="color:#b91c1c;">Retirer</button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    @if($nowTop !== null)
                        <div class="absolute" style="top: {{ number_format($nowTop, 2, '.', '') }}%; left: 72px; right: 0; height: 2px; background:#2563eb; z-index: 4;">
                            <span class="absolute rounded-full" style="left: -5px; top: -4px; width: 10px; height: 10px; background:#2563eb;"></span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.calendrierBoot = @json($cal);
function calendrierEmploi(cal) {
    const teintes = {
        groupe: { bg: '#dbeafe', border: '#93c5fd', text: '#1e3a8a' },
        enseignant: { bg: '#d1fae5', border: '#6ee7b7', text: '#065f46' },
        salle: { bg: '#ede9fe', border: '#c4b5fd', text: '#5b21b6' },
        apercu: { bg: '#fff7ed', border: '#fdba74', text: '#9a3412' },
    };
    const debutJour = 8 * 60;
    const finJour = 17 * 60;
    const span = finJour - debutJour;

    function minutes(valeur) {
        const parts = String(valeur || '00:00').substring(0, 5).split(':');
        return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
    }

    function heureLabel(valeur) {
        const total = minutes(valeur);
        const h = String(Math.floor(total / 60)).padStart(2, '0');
        const m = String(total % 60).padStart(2, '0');
        return m === '00' ? h + 'h' : h + 'h' + m;
    }

    function iso(date) {
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + m + '-' + d;
    }

    function lundiDe(valeur) {
        if (!valeur) return null;
        const date = new Date(valeur + 'T12:00:00');
        if (Number.isNaN(date.getTime())) return null;
        const jour = date.getDay();
        date.setDate(date.getDate() + (jour === 0 ? -6 : 1 - jour));
        return iso(date);
    }

    function el(tag, styles, text) {
        const node = document.createElement(tag);
        if (styles) node.setAttribute('style', styles);
        if (text !== undefined && text !== null && text !== '') node.textContent = text;
        return node;
    }

    return {
        lecture: !!cal.lecture,
        peutCreer: !!cal.peutCreer,
        groupeId: cal.groupeId ? String(cal.groupeId) : '',
        enseignantId: cal.enseignantId ? String(cal.enseignantId) : '',
        salleId: cal.salleId ? String(cal.salleId) : '',
        date: cal.date || '',
        debut: (cal.debut || '08:00').substring(0, 5),
        fin: (cal.fin || '09:00').substring(0, 5),
        monday: cal.monday,
        label: cal.label,
        seances: cal.seances || [],
        groupes: cal.groupes || [],
        indexUrl: cal.indexUrl,
        destroyBase: cal.destroyBase,
        csrf: cal.csrf,
        conflit: !!cal.conflit,
        vide: !!cal.vide,

        nomGroupe() {
            const id = parseInt(this.groupeId, 10);
            const groupe = this.groupes.find((ligne) => ligne.id === id);
            return groupe ? groupe.nom : '';
        },

        async decaler(semaines) {
            const date = new Date(this.monday + 'T12:00:00');
            date.setDate(date.getDate() + semaines * 7);
            this.date = iso(date);
            await this.suivreDate();
        },

        async suivreDate() {
            const monday = lundiDe(this.date);
            if (!monday || monday === this.monday) {
                this.dessiner();
                return;
            }
            const url = new URL(this.indexUrl, window.location.origin);
            url.searchParams.set('semaine', monday);
            if (this.groupeId) url.searchParams.set('groupe', this.groupeId);
            if (this.enseignantId) url.searchParams.set('enseignant', this.enseignantId);
            if (this.salleId) url.searchParams.set('salle', this.salleId);
            const reponse = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!reponse.ok) return;
            const data = await reponse.json();
            this.monday = data.monday;
            this.label = data.label;
            this.seances = data.seances || [];
            this.vide = this.seances.length === 0;
            this.dessiner();
            window.history.replaceState({}, '', url);
        },

        evenements() {
            const gid = parseInt(this.groupeId, 10) || null;
            const eid = parseInt(this.enseignantId, 10) || null;
            const sid = parseInt(this.salleId, 10) || null;
            const items = [];
            this.seances.forEach((seance) => {
                const groupe = gid && seance.groupe_id === gid;
                const prof = eid && seance.enseignant_id === eid;
                const salle = sid && seance.salle_id === sid;
                if (!groupe && !prof && !salle) return;
                const kind = groupe ? 'groupe' : (prof ? 'enseignant' : 'salle');
                const tags = [groupe ? 'Groupe' : null, prof ? 'Prof' : null, salle ? 'Salle' : null].filter(Boolean);
                items.push({
                    id: seance.id,
                    date: seance.date,
                    start: minutes(seance.debut),
                    end: minutes(seance.fin),
                    kind: kind,
                    titre: seance.matiere,
                    ligne: groupe ? (seance.professeur || '') : (seance.groupe || ''),
                    meta: [seance.salle, heureLabel(seance.debut) + ' – ' + heureLabel(seance.fin)].filter(Boolean).join(' · '),
                    tags: tags.join(' · '),
                    supprimable: groupe && !this.lecture,
                });
            });
            if (this.peutCreer) {
                const preview = this.apercu();
                if (preview) items.push(preview);
            }
            const preview = items.find((item) => item.kind === 'apercu');
            this.conflit = !!(preview && items.some((item) => item.kind !== 'apercu' && item.date === preview.date && item.start < preview.end && preview.start < item.end));
            return items;
        },

        apercu() {
            const monday = lundiDe(this.date);
            if (!this.date || !monday || monday !== this.monday) return null;
            const date = new Date(this.date + 'T12:00:00');
            const jour = date.getDay();
            if (jour === 0 || jour === 6) return null;
            const start = minutes(this.debut);
            const end = minutes(this.fin);
            if (end <= start) return null;
            return {
                id: null,
                date: this.date,
                start: start,
                end: end,
                kind: 'apercu',
                titre: 'Nouveau créneau',
                ligne: '',
                meta: heureLabel(this.debut) + ' – ' + heureLabel(this.fin),
                tags: 'Aperçu',
                supprimable: false,
            };
        },

        placer(items) {
            const normalized = items.map((item) => {
                const start = Math.max(debutJour, item.start);
                const end = Math.min(finJour, item.end);
                return Object.assign({}, item, { start: start, end: Math.max(start + 15, end) });
            }).sort((a, b) => a.start - b.start);
            const columnEnds = [];
            normalized.forEach((item) => {
                let column = columnEnds.findIndex((end) => item.start >= end);
                if (column === -1) {
                    column = columnEnds.length;
                    columnEnds.push(item.end);
                } else {
                    columnEnds[column] = item.end;
                }
                item.column = column;
            });
            const count = Math.max(1, columnEnds.length);
            return normalized.map((item) => Object.assign(item, {
                top: ((item.start - debutJour) / span) * 100,
                height: ((item.end - item.start) / span) * 100,
                left: (item.column / count) * 100,
                width: (100 / count),
            }));
        },

        dessiner() {
            const root = this.$refs.grille;
            if (!root) return;
            const items = this.evenements();
            const monday = new Date(this.monday + 'T12:00:00');
            const jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
            const wrap = el('div');
            wrap.style.minWidth = '920px';
            const head = el('div');
            head.style.display = 'grid';
            head.style.gridTemplateColumns = '72px repeat(5, minmax(0, 1fr))';
            head.appendChild(el('div', 'padding:8px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#94a3b8;', 'Heure'));
            jours.forEach((nom, index) => {
                const date = new Date(monday.getTime());
                date.setDate(monday.getDate() + index);
                const cle = iso(date);
                const cellule = el('div', 'padding:8px 12px;color:#fff;background:' + (cle === this.date ? '#1d4ed8' : '#1f2937') + ';');
                cellule.appendChild(el('div', 'font-size:11px;font-weight:500;letter-spacing:.05em;text-transform:uppercase;color:#cbd5e1;', nom));
                cellule.appendChild(el('div', 'font-size:20px;font-weight:700;line-height:1;margin-top:2px;', String(date.getDate())));
                head.appendChild(cellule);
            });
            wrap.appendChild(head);

            const corps = el('div', 'position:relative;height:576px;');
            const grille = el('div', 'display:grid;grid-template-columns:72px repeat(5, minmax(0, 1fr));height:100%;');
            const heures = el('div', 'position:relative;border-right:1px solid #e2e8f0;background:#fff;');
            for (let heure = 8; heure <= 17; heure++) {
                const top = ((heure * 60 - debutJour) / span) * 100;
                const shift = heure === 8 ? '0' : (heure === 17 ? '-100%' : '-50%');
                heures.appendChild(el('span', 'position:absolute;right:8px;top:' + top + '%;transform:translateY(' + shift + ');font-size:11px;font-weight:500;color:#94a3b8;', String(heure).padStart(2, '0') + 'h'));
            }
            grille.appendChild(heures);

            jours.forEach((nom, index) => {
                const date = new Date(monday.getTime());
                date.setDate(monday.getDate() + index);
                const cle = iso(date);
                const colonne = el('div', 'position:relative;border-left:1px solid #f1f5f9;background:' + (cle === this.date ? '#eff6ff' : '#fff') + ';');
                for (let heure = 9; heure <= 17; heure++) {
                    const top = ((heure * 60 - debutJour) / span) * 100;
                    colonne.appendChild(el('div', 'position:absolute;left:0;right:0;top:' + top + '%;border-top:1px dashed #e2e8f0;'));
                }
                this.placer(items.filter((item) => item.date === cle)).forEach((event) => {
                    const teinte = teintes[event.kind] || teintes.groupe;
                    const bloc = el('div', 'position:absolute;overflow:hidden;border-radius:6px;padding:4px 8px;z-index:2;top:' + event.top.toFixed(2) + '%;height:' + event.height.toFixed(2) + '%;left:calc(' + event.left.toFixed(2) + '% + 4px);width:calc(' + event.width.toFixed(2) + '% - 8px);background:' + teinte.bg + ';border:1px ' + (event.kind === 'apercu' ? 'dashed' : 'solid') + ' ' + teinte.border + ';');
                    bloc.appendChild(el('p', 'font-size:11px;font-weight:700;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:' + teinte.text + ';', event.titre));
                    if (event.ligne) bloc.appendChild(el('p', 'font-size:10px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:' + teinte.text + ';', event.ligne));
                    if (event.meta) bloc.appendChild(el('p', 'font-size:10px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#475569;', event.meta));
                    if (event.tags) bloc.appendChild(el('p', 'font-size:10px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#64748b;', event.tags));
                    if (event.supprimable && event.id) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = this.destroyBase + '/' + event.id;
                        form.addEventListener('submit', (e) => {
                            if (!confirm('Retirer ce créneau ?')) e.preventDefault();
                        });
                        const token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = this.csrf;
                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';
                        const bouton = el('button', 'font-size:10px;font-weight:600;color:#b91c1c;background:transparent;border:0;padding:0;cursor:pointer;', 'Retirer');
                        bouton.type = 'submit';
                        form.appendChild(token);
                        form.appendChild(method);
                        form.appendChild(bouton);
                        bloc.appendChild(form);
                    }
                    colonne.appendChild(bloc);
                });
                grille.appendChild(colonne);
            });
            corps.appendChild(grille);
            wrap.appendChild(corps);
            root.replaceChildren(wrap);
        },
    };
}
</script>
@endpush
