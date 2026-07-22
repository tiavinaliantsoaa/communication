<?php

namespace App\Http\Controllers;

use App\Models\EditorialEvent;
use App\Models\EditorialEventVisuel;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalendrierEditorialController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->get('view', '2weeks');
        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))->startOfDay()
            : now()->startOfDay();

        [$rangeStart, $rangeEnd] = $this->resolveRange($date, $view);

        $events = EditorialEvent::query()
            ->with('visuels')
            ->whereDate('date_debut', '<=', $rangeEnd)
            ->whereRaw('COALESCE(date_fin, date_debut) >= ?', [$rangeStart->toDateString()])
            ->orderBy('date_debut')
            ->get()
            ->map(fn (EditorialEvent $event) => $this->mapEvent($event));

        $categories = collect(EditorialEvent::CATEGORIES)->map(fn ($meta, $key) => [
            'key' => $key,
            'label' => $meta['label'],
            'color_name' => $meta['color_name'],
            'color' => $meta['color'],
        ])->values();

        $title = 'Calendrier éditorial';
        $subtitle = 'Planification des contenus et actions de communication';

        return view('calendrier-editorial.index', [
            'title' => $title,
            'subtitle' => $subtitle,
            'view' => $view,
            'currentDate' => $date->toDateString(),
            'rangeStart' => $rangeStart->toDateString(),
            'rangeEnd' => $rangeEnd->toDateString(),
            'rangeLabel' => $this->formatRangeLabel($rangeStart, $rangeEnd),
            'events' => $events,
            'categories' => $categories,
            'moisLabel' => $date->locale('fr')->isoFormat('MMMM YYYY'),
            'statuts' => [
                'planifie' => 'Planifié',
                'en_cours' => 'En cours',
                'publie' => 'Publié',
                'annule' => 'Annulé',
            ],
            'typesContenu' => EditorialEvent::TYPES_CONTENU,
            'canValidate' => auth()->user()?->canValidateEditorial() ?? false,
            'maxVisuels' => EditorialEvent::MAX_VISUELS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEvent($request);
        $files = $this->validatedVisuelFiles($request);

        $event = null;
        DB::transaction(function () use ($validated, $files, &$event) {
            $event = EditorialEvent::create($validated);
            $this->storeVisuelFiles($event, $files);
            $this->syncLegacyVisuelColumns($event);
        });

        app(ActivityLogger::class)->log(
            'editorial',
            auth()->user()->name.' a ajouté « '.$event->titre.' » au calendrier éditorial',
            auth()->user(),
            'create',
            'Calendrier éditorial',
            route('calendrier-editorial', ['date' => $event->date_debut->toDateString()]),
            $event
        );

        return $this->redirectToCalendar($request)
            ->with('success', 'Contenu ajouté au calendrier.');
    }

    public function update(Request $request, EditorialEvent $editorialEvent)
    {
        $validated = $this->validateEvent($request);
        $files = $this->validatedVisuelFiles($request);
        $removeIds = array_values(array_filter(array_map('intval', (array) $request->input('remove_visuel_ids', []))));

        DB::transaction(function () use ($editorialEvent, $validated, $files, $removeIds) {
            $editorialEvent->load('visuels');

            if ($removeIds) {
                $editorialEvent->visuels()
                    ->whereIn('id', $removeIds)
                    ->get()
                    ->each(fn (EditorialEventVisuel $v) => $v->delete());
                $editorialEvent->unsetRelation('visuels');
                $editorialEvent->load('visuels');
            }

            $remaining = $editorialEvent->visuels->count();
            if ($remaining + count($files) > EditorialEvent::MAX_VISUELS) {
                throw ValidationException::withMessages([
                    'visuels' => 'Maximum '.EditorialEvent::MAX_VISUELS.' images au total ('. $remaining.' déjà présentes).',
                ]);
            }

            $editorialEvent->update($validated);
            $this->storeVisuelFiles($editorialEvent, $files, $remaining);
            $this->syncLegacyVisuelColumns($editorialEvent->fresh('visuels'));
        });

        app(ActivityLogger::class)->log(
            'editorial',
            auth()->user()->name.' a modifié « '.$editorialEvent->titre.' » dans le calendrier éditorial',
            auth()->user(),
            'update',
            'Calendrier éditorial',
            route('calendrier-editorial', ['date' => $editorialEvent->date_debut->toDateString()]),
            $editorialEvent
        );

        return $this->redirectToCalendar($request)
            ->with('success', 'Contenu mis à jour.');
    }

    public function destroy(Request $request, EditorialEvent $editorialEvent)
    {
        $titre = $editorialEvent->titre;
        $date = optional($editorialEvent->date_debut)->toDateString();
        $editorialEvent->delete();

        app(ActivityLogger::class)->log(
            'editorial',
            auth()->user()->name.' a supprimé « '.$titre.' » du calendrier éditorial',
            auth()->user(),
            'delete',
            'Calendrier éditorial',
            route('calendrier-editorial', ['date' => $date ?? now()->toDateString()])
        );

        return $this->redirectToCalendar($request)
            ->with('success', 'Contenu supprimé du calendrier.');
    }

    private function validateEvent(Request $request): array
    {
        $categories = array_values(array_unique(array_filter((array) $request->input('categorie', []))));
        $request->merge(['categorie' => $categories]);

        $isFacebookFi = in_array('facebook', $categories, true)
            && $request->input('type_contenu') === 'FI';
        $hasLinkedIn = in_array('linkedin', $categories, true);

        $rules = [
            'titre' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'array', 'min:1'],
            'categorie.*' => ['required', Rule::in(array_keys(EditorialEvent::CATEGORIES))],
            'type_contenu' => ['required', Rule::in(array_keys(EditorialEvent::TYPES_CONTENU))],
            'booster' => ['nullable', 'boolean'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::in(['planifie', 'en_cours', 'publie', 'annule'])],
            'valide' => ['nullable', 'boolean'],
            'texte_publication' => ['required', 'string', 'max:5000'],
            'texte_publication_linkedin' => [$hasLinkedIn ? 'required' : 'nullable', 'string', 'max:5000'],
            'visuels' => ['nullable', 'array', 'max:'.EditorialEvent::MAX_VISUELS],
            'visuels.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_visuel_ids' => ['nullable', 'array'],
            'remove_visuel_ids.*' => ['integer'],
        ];

        if ($isFacebookFi && $request->boolean('booster')) {
            $rules['date_fin'] = ['required', 'date', 'after_or_equal:date_debut'];
        }

        $validated = $request->validate($rules, [
            'categorie.required' => 'Sélectionnez au moins une catégorie.',
            'categorie.min' => 'Sélectionnez au moins une catégorie.',
            'texte_publication.required' => 'Le texte de publication est obligatoire.',
            'texte_publication_linkedin.required' => 'Le texte de publication LinkedIn est obligatoire.',
            'type_contenu.required' => 'Veuillez choisir FI ou FP.',
            'date_fin.required' => 'La date de fin est obligatoire lorsque Booster est activé.',
            'visuels.max' => 'Vous pouvez envoyer au maximum '.EditorialEvent::MAX_VISUELS.' images.',
            'visuels.*.mimes' => 'Chaque visuel doit être une image (jpg, png, webp, gif).',
            'visuels.*.max' => 'Chaque visuel ne doit pas dépasser 5 Mo.',
        ]);

        unset($validated['visuels'], $validated['remove_visuel_ids']);

        $validated['categorie'] = array_values(array_unique($validated['categorie']));
        $validated['booster'] = $isFacebookFi && $request->boolean('booster');
        $validated['valide'] = auth()->user()?->canValidateEditorial()
            ? $request->boolean('valide')
            : false;

        if (! $isFacebookFi) {
            $validated['booster'] = false;
        }

        if (! $validated['booster']) {
            $validated['date_fin'] = $validated['date_fin'] ?? null;
        }

        if (! $hasLinkedIn) {
            $validated['texte_publication_linkedin'] = null;
        }

        return $validated;
    }

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function validatedVisuelFiles(Request $request): array
    {
        $files = $request->file('visuels', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        // Backward compatibility: single "visuel" field
        if ($request->hasFile('visuel')) {
            $files[] = $request->file('visuel');
        }

        return array_values(array_filter($files));
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    private function storeVisuelFiles(EditorialEvent $event, array $files, int $startPosition = 0): void
    {
        foreach ($files as $index => $file) {
            $event->visuels()->create([
                'path' => $file->store('editorial-visuels', 'public'),
                'nom' => $file->getClientOriginalName(),
                'position' => $startPosition + $index,
            ]);
        }
    }

    private function syncLegacyVisuelColumns(EditorialEvent $event): void
    {
        $first = $event->visuels()->orderBy('position')->orderBy('id')->first();

        $event->forceFill([
            'visuel_path' => $first?->path,
            'visuel_nom' => $first?->nom,
        ])->saveQuietly();
    }

    private function redirectToCalendar(Request $request)
    {
        $date = $request->input('return_date', $request->input('date_debut', now()->toDateString()));
        $view = $request->input('return_view', '2weeks');

        return redirect()->route('calendrier-editorial', [
            'date' => $date,
            'view' => $view,
        ]);
    }

    private function mapEvent(EditorialEvent $event): array
    {
        $meta = $event->categorie_meta;
        $visuels = $event->visuels->map->toArrayPayload()->values()->all();

        return [
            'id' => $event->id,
            'titre' => $event->titre,
            'categorie' => $event->categorie,
            'categories' => $event->categories_meta,
            'label' => $event->categories_label,
            'color' => $meta['color'],
            'text' => $meta['text'],
            'type_contenu' => $event->type_contenu,
            'booster' => (bool) $event->booster,
            'date_debut' => $event->date_debut->toDateString(),
            'date_fin' => ($event->date_fin ?? $event->date_debut)->toDateString(),
            'statut' => $event->statut,
            'valide' => (bool) $event->valide,
            'texte_publication' => $event->texte_publication,
            'texte_publication_linkedin' => $event->texte_publication_linkedin,
            'visuels' => $visuels,
            'visuel_url' => $visuels[0]['url'] ?? $event->visuel_url,
            'visuel_nom' => $visuels[0]['nom'] ?? $event->visuel_nom,
            'update_url' => route('calendrier-editorial.update', $event),
            'delete_url' => route('calendrier-editorial.destroy', $event),
        ];
    }

    private function resolveRange(Carbon $date, string $view): array
    {
        return match ($view) {
            'day' => [$date->copy(), $date->copy()],
            'week' => [$date->copy()->startOfWeek(Carbon::MONDAY), $date->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [$date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY), $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)],
            'list' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [
                $date->copy()->startOfWeek(Carbon::MONDAY),
                $date->copy()->startOfWeek(Carbon::MONDAY)->addDays(13),
            ],
        };
    }

    private function formatRangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->locale('fr')->isoFormat('D MMMM YYYY');
        }

        if ($start->isSameMonth($end)) {
            return $start->locale('fr')->isoFormat('D')
                .' – '
                .$end->locale('fr')->isoFormat('D MMMM YYYY');
        }

        return $start->locale('fr')->isoFormat('D MMM')
            .' – '
            .$end->locale('fr')->isoFormat('D MMM YYYY');
    }
}
