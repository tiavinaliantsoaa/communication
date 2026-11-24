<?php

namespace App\Http\Controllers;

use App\Models\EditorialEvent;
use App\Models\EditorialEventVisuel;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalendrierEditorialController extends Controller
{
    public function index(Request $request)
    {
        $service = $this->resolveService($request);
        $meta = EditorialEvent::serviceMeta($service);
        $view = $request->get('view', '2weeks');
        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))->startOfDay()
            : now()->startOfDay();

        [$rangeStart, $rangeEnd] = $this->resolveRange($date, $view);

        $events = EditorialEvent::query()
            ->forService($service)
            ->with('visuels')
            ->whereDate('date_debut', '<=', $rangeEnd)
            ->whereRaw('COALESCE(date_fin, date_debut) >= ?', [$rangeStart->toDateString()])
            ->orderBy('date_debut')
            ->get()
            ->map(fn (EditorialEvent $event) => $this->mapEvent($event, $meta));

        $categories = collect(EditorialEvent::CATEGORIES)->map(fn ($catMeta, $key) => [
            'key' => $key,
            'label' => $catMeta['label'],
            'color_name' => $catMeta['color_name'],
            'color' => $catMeta['color'],
        ])->values();

        $prefix = $meta['route_prefix'];

        return view('calendrier-editorial.index', [
            'title' => $meta['label'],
            'subtitle' => $meta['subtitle'],
            'calendarLabel' => $meta['label'],
            'service' => $service,
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
            'canValidate' => auth()->user()?->canValidateCalendar($service) ?? false,
            'maxVisuels' => EditorialEvent::MAX_VISUELS,
            'supportsReel' => $this->supportsReel($service),
            'baseUrl' => route($prefix),
            'searchUrl' => route($prefix.'.search'),
            'storeUrl' => route($prefix.'.store'),
        ]);
    }

    public function search(Request $request)
    {
        $service = $this->resolveService($request);
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json(['matches' => []]);
        }

        $matches = EditorialEvent::query()
            ->forService($service)
            ->where('titre', 'like', '%'.$q.'%')
            ->orderBy('date_debut')
            ->limit(100)
            ->get(['id', 'titre', 'categorie', 'booster', 'date_debut', 'date_fin'])
            ->map(fn (EditorialEvent $event) => [
                'id' => $event->id,
                'titre' => $event->titre,
                'categorie' => $event->categorie,
                'booster' => (bool) $event->booster,
                'date_debut' => $event->date_debut->toDateString(),
                'date_fin' => ($event->date_fin ?? $event->date_debut)->toDateString(),
            ]);

        return response()->json(['matches' => $matches]);
    }

    public function store(Request $request)
    {
        $service = $this->resolveService($request);
        $meta = EditorialEvent::serviceMeta($service);
        $validated = $this->validateEvent($request, $service);
        $visualType = $this->visualType($request, $service);
        $files = $visualType === 'images' ? $this->validatedVisuelFiles($request) : [];
        $reelFile = $visualType === 'reel' ? $request->file('reel') : null;
        $categories = array_values($validated['categorie'] ?? []);

        if ($categories === []) {
            throw ValidationException::withMessages([
                'categorie' => 'Sélectionnez au moins une catégorie.',
            ]);
        }

        $created = [];

        DB::transaction(function () use ($validated, $files, $reelFile, $categories, $service, &$created) {
            $storedVisuels = [];
            foreach ($files as $index => $file) {
                $storedVisuels[] = [
                    'path' => $file->store('editorial-visuels', 'public'),
                    'nom' => $file->getClientOriginalName(),
                    'position' => $index,
                ];
            }

            $storedReel = null;
            if ($reelFile) {
                $storedReel = [
                    'path' => $reelFile->store('editorial-reels', 'public'),
                    'nom' => $reelFile->getClientOriginalName(),
                ];
            }

            foreach ($categories as $index => $category) {
                $payload = $validated;
                $payload['service'] = $service;
                $payload['categorie'] = [$category];

                $isFacebookFi = $category === 'facebook'
                    && ($payload['type_contenu'] ?? '') === 'FI';

                if (! $isFacebookFi) {
                    $payload['booster'] = false;
                }

                if (! $payload['booster']) {
                    $payload['date_fin'] = $payload['date_fin'] ?? null;
                }

                $event = EditorialEvent::create($payload);

                foreach ($storedVisuels as $visuel) {
                    $path = $visuel['path'];
                    if ($index > 0 && $path) {
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        $copyPath = 'editorial-visuels/'.uniqid('v_', true).($ext ? '.'.$ext : '');
                        Storage::disk('public')->copy($path, $copyPath);
                        $path = $copyPath;
                    }

                    $event->visuels()->create([
                        'path' => $path,
                        'nom' => $visuel['nom'],
                        'position' => $visuel['position'],
                    ]);
                }

                $this->syncLegacyVisuelColumns($event->fresh('visuels'));
                $this->attachReelCopy($event, $storedReel, $index > 0);
                $created[] = $event;
            }
        });

        $first = $created[0];
        $count = count($created);
        $prefix = $meta['route_prefix'];

        app(ActivityLogger::class)->log(
            $meta['module'],
            auth()->user()->name.' a ajouté « '.$first->titre.' » au '.$meta['label']
                .($count > 1 ? ' ('.$count.' catégories)' : ''),
            auth()->user(),
            'create',
            $meta['label'],
            route($prefix, ['date' => $first->date_debut->toDateString()]),
            $first
        );

        $message = $count > 1
            ? $count.' contenus ajoutés (une entrée par catégorie).'
            : 'Contenu ajouté au calendrier.';

        return $this->redirectToCalendar($request, $service)
            ->with('success', $message);
    }

    public function update(Request $request, EditorialEvent $editorialEvent)
    {
        $service = $this->resolveService($request);
        $this->assertEventService($editorialEvent, $service);
        $meta = EditorialEvent::serviceMeta($service);
        $validated = $this->validateEvent($request, $service, $editorialEvent);
        $visualType = $this->visualType($request, $service);
        $files = $visualType === 'images' ? $this->validatedVisuelFiles($request) : [];
        $reelFile = $visualType === 'reel' ? $request->file('reel') : null;
        $removeReel = $this->supportsReel($service) && ($visualType === 'images' || $request->boolean('remove_reel'));
        $dropVisuels = $visualType === 'reel';
        $removeIds = array_values(array_filter(array_map('intval', (array) $request->input('remove_visuel_ids', []))));
        $categories = array_values($validated['categorie'] ?? []);

        if ($categories === []) {
            throw ValidationException::withMessages([
                'categorie' => 'Sélectionnez au moins une catégorie.',
            ]);
        }

        $createdCount = 0;

        DB::transaction(function () use ($editorialEvent, $validated, $files, $reelFile, $removeReel, $removeIds, $dropVisuels, $categories, $service, &$createdCount) {
            $editorialEvent->load('visuels');

            if ($dropVisuels) {
                $editorialEvent->visuels
                    ->each(fn (EditorialEventVisuel $v) => $v->delete());
                $editorialEvent->unsetRelation('visuels');
                $editorialEvent->load('visuels');
            } elseif ($removeIds) {
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
                    'visuels' => 'Maximum '.EditorialEvent::MAX_VISUELS.' images au total ('.$remaining.' déjà présentes).',
                ]);
            }

            // Contenu actuel = 1ʳᵉ catégorie ; les autres créent des doublons (comme à la création)
            $primaryCategory = $categories[0];
            $extraCategories = array_slice($categories, 1);

            $payload = $validated;
            $payload['categorie'] = [$primaryCategory];
            $isFacebookFi = $primaryCategory === 'facebook'
                && ($payload['type_contenu'] ?? '') === 'FI';
            if (! $isFacebookFi) {
                $payload['booster'] = false;
                $payload['date_fin'] = $payload['date_fin'] ?? null;
            }

            $editorialEvent->update($payload);
            $this->storeVisuelFiles($editorialEvent, $files, $remaining);
            $this->syncLegacyVisuelColumns($editorialEvent->fresh('visuels'));
            $this->syncReel($editorialEvent, $reelFile, $removeReel);

            if ($extraCategories === []) {
                return;
            }

            $sourceVisuels = $editorialEvent->fresh('visuels')->visuels;
            foreach ($extraCategories as $category) {
                $duplicatePayload = $validated;
                $duplicatePayload['service'] = $service;
                $duplicatePayload['categorie'] = [$category];

                $dupIsFacebookFi = $category === 'facebook'
                    && ($duplicatePayload['type_contenu'] ?? '') === 'FI';
                if (! $dupIsFacebookFi) {
                    $duplicatePayload['booster'] = false;
                    $duplicatePayload['date_fin'] = $duplicatePayload['date_fin'] ?? null;
                }

                $duplicate = EditorialEvent::create($duplicatePayload);

                foreach ($sourceVisuels as $visuel) {
                    $path = $visuel->path;
                    if ($path && Storage::disk('public')->exists($path)) {
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        $copyPath = 'editorial-visuels/'.uniqid('v_', true).($ext ? '.'.$ext : '');
                        Storage::disk('public')->copy($path, $copyPath);
                        $path = $copyPath;
                    }

                    $duplicate->visuels()->create([
                        'path' => $path,
                        'nom' => $visuel->nom,
                        'position' => $visuel->position,
                    ]);
                }

                $this->syncLegacyVisuelColumns($duplicate->fresh('visuels'));
                $this->attachReelCopy($duplicate, [
                    'path' => $editorialEvent->reel_path,
                    'nom' => $editorialEvent->reel_nom,
                ], true);
                $createdCount++;
            }
        });

        $prefix = $meta['route_prefix'];
        $editorialEvent->refresh();

        app(ActivityLogger::class)->log(
            $meta['module'],
            auth()->user()->name.' a modifié « '.$editorialEvent->titre.' » dans le '.$meta['label']
                .($createdCount > 0 ? ' (+'.$createdCount.' doublon'.($createdCount > 1 ? 's' : '').')' : ''),
            auth()->user(),
            'update',
            $meta['label'],
            route($prefix, ['date' => $editorialEvent->date_debut->toDateString()]),
            $editorialEvent
        );

        $message = $createdCount > 0
            ? 'Contenu mis à jour et '.$createdCount.' doublon'.($createdCount > 1 ? 's créés' : ' créé').' (une entrée par catégorie).'
            : 'Contenu mis à jour.';

        return $this->redirectToCalendar($request, $service)
            ->with('success', $message);
    }

    public function destroy(Request $request, EditorialEvent $editorialEvent)
    {
        $service = $this->resolveService($request);
        $this->assertEventService($editorialEvent, $service);
        $meta = EditorialEvent::serviceMeta($service);
        $titre = $editorialEvent->titre;
        $date = optional($editorialEvent->date_debut)->toDateString();
        $editorialEvent->delete();

        $prefix = $meta['route_prefix'];

        app(ActivityLogger::class)->log(
            $meta['module'],
            auth()->user()->name.' a supprimé « '.$titre.' » du '.$meta['label'],
            auth()->user(),
            'delete',
            $meta['label'],
            route($prefix, ['date' => $date ?? now()->toDateString()])
        );

        return $this->redirectToCalendar($request, $service)
            ->with('success', 'Contenu supprimé du calendrier.');
    }

    private function resolveService(Request $request): string
    {
        return EditorialEvent::serviceFromRouteName($request->route()?->getName());
    }

    private function assertEventService(EditorialEvent $event, string $service): void
    {
        if (($event->service ?: EditorialEvent::SERVICE_EDITORIAL) !== $service) {
            abort(404);
        }
    }

    private function validateEvent(Request $request, string $service, ?EditorialEvent $existing = null): array
    {
        $categories = array_values(array_unique(array_filter((array) $request->input('categorie', []))));
        $request->merge(['categorie' => $categories]);

        $isFacebookFi = in_array('facebook', $categories, true)
            && $request->input('type_contenu') === 'FI';

        $rules = [
            'titre' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'array', 'min:1'],
            'categorie.*' => ['required', Rule::in(array_keys(EditorialEvent::CATEGORIES))],
            'type_contenu' => ['required', Rule::in(array_keys(EditorialEvent::TYPES_CONTENU))],
            'booster' => ['nullable', 'boolean'],
            'urgent' => ['nullable', 'boolean'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::in(['planifie', 'en_cours', 'publie', 'annule'])],
            'valide' => ['nullable', 'boolean'],
            'texte_publication' => ['nullable', 'string', 'max:5000'],
            'texte_publication_linkedin' => ['nullable', 'string', 'max:5000'],
            'remove_visuel_ids' => ['nullable', 'array'],
            'remove_visuel_ids.*' => ['integer'],
        ];

        $visualType = $this->visualType($request, $service);
        if ($visualType === 'images') {
            $rules['visuels'] = ['nullable', 'array', 'max:'.EditorialEvent::MAX_VISUELS];
            $rules['visuels.*'] = ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'];
        }

        if ($this->supportsReel($service)) {
            $rules['type_visuel'] = ['nullable', Rule::in(['images', 'reel'])];
            if ($visualType === 'reel') {
                $rules['reel'] = ['nullable', 'file', 'max:20480', function (string $attribute, $value, \Closure $fail) {
                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    if (! in_array($extension, ['mp4', 'webm', 'mov', 'm4v'], true)) {
                        $fail('Le reel doit être une vidéo MP4, WEBM ou MOV.');
                    }
                }];
                $rules['remove_reel'] = ['nullable', 'boolean'];
            }
        }

        if ($isFacebookFi && $request->boolean('booster')) {
            $rules['date_fin'] = ['required', 'date', 'after_or_equal:date_debut'];
        }

        $validated = $request->validate($rules, [
            'categorie.required' => 'Sélectionnez au moins une catégorie.',
            'categorie.min' => 'Sélectionnez au moins une catégorie.',
            'type_contenu.required' => 'Veuillez choisir FI ou FP.',
            'date_fin.required' => 'La date de fin est obligatoire lorsque Booster est activé.',
            'visuels.max' => 'Vous pouvez envoyer au maximum '.EditorialEvent::MAX_VISUELS.' images.',
            'visuels.*.mimes' => 'Chaque visuel doit être une image (jpg, png, webp, gif).',
            'visuels.*.max' => 'Chaque visuel ne doit pas dépasser 5 Mo.',
            'reel.max' => 'Le reel ne doit pas dépasser 20 Mo.',
            'reel.uploaded' => 'Le reel n’a pas pu être envoyé. La taille maximale est de 20 Mo.',
        ]);

        unset($validated['visuels'], $validated['remove_visuel_ids'], $validated['reel'], $validated['remove_reel'], $validated['type_visuel']);

        $validated['categorie'] = array_values(array_unique($validated['categorie']));
        $validated['booster'] = $isFacebookFi && $request->boolean('booster');
        $validated['urgent'] = $request->boolean('urgent');
        // The Validé checkbox is omitted for users who cannot validate. Keep the
        // stored flag on edit so a routine correction does not clear an approval.
        $validated['valide'] = auth()->user()?->canValidateCalendar($service)
            ? $request->boolean('valide')
            : (bool) ($existing?->valide ?? false);

        if (! $isFacebookFi) {
            $validated['booster'] = false;
        }

        if (! $validated['booster']) {
            $validated['date_fin'] = $validated['date_fin'] ?? null;
        }

        // Un seul champ texte pour toutes les catégories (y compris LinkedIn)
        $validated['texte_publication_linkedin'] = null;

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

    private function supportsReel(string $service): bool
    {
        return in_array($service, [EditorialEvent::SERVICE_EDITORIAL, EditorialEvent::SERVICE_SAAN], true);
    }

    private function visualType(Request $request, string $service): string
    {
        if (! $this->supportsReel($service)) {
            return 'images';
        }

        return $request->input('type_visuel') === 'reel' ? 'reel' : 'images';
    }

    /**
     * @param  array{path?: string|null, nom?: string|null}|null  $storedReel
     */
    private function attachReelCopy(EditorialEvent $event, ?array $storedReel, bool $copy): void
    {
        $path = $storedReel['path'] ?? null;
        if (! $path) {
            return;
        }

        if ($copy) {
            if (! Storage::disk('public')->exists($path)) {
                return;
            }
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $copyPath = 'editorial-reels/'.uniqid('r_', true).($extension ? '.'.$extension : '');
            Storage::disk('public')->copy($path, $copyPath);
            $path = $copyPath;
        }

        $event->forceFill([
            'reel_path' => $path,
            'reel_nom' => $storedReel['nom'] ?? null,
        ])->save();
    }

    private function syncReel(EditorialEvent $event, mixed $file, bool $remove): void
    {
        if (! $remove && ! $file) {
            return;
        }

        if ($event->reel_path) {
            Storage::disk('public')->delete($event->reel_path);
        }

        if ($file) {
            $event->forceFill([
                'reel_path' => $file->store('editorial-reels', 'public'),
                'reel_nom' => $file->getClientOriginalName(),
            ])->save();

            return;
        }

        $event->forceFill([
            'reel_path' => null,
            'reel_nom' => null,
        ])->save();
    }

    private function syncLegacyVisuelColumns(EditorialEvent $event): void
    {
        $first = $event->visuels()->orderBy('position')->orderBy('id')->first();

        $event->forceFill([
            'visuel_path' => $first?->path,
            'visuel_nom' => $first?->nom,
        ])->saveQuietly();
    }

    private function redirectToCalendar(Request $request, string $service)
    {
        $date = $request->input('return_date', $request->input('date_debut', now()->toDateString()));
        $view = $request->input('return_view', '2weeks');
        $prefix = EditorialEvent::serviceMeta($service)['route_prefix'];

        return redirect()->route($prefix, [
            'date' => $date,
            'view' => $view,
        ]);
    }

    private function mapEvent(EditorialEvent $event, array $meta): array
    {
        $catMeta = $event->categorie_meta;
        $visuels = $event->visuels->map->toArrayPayload()->values()->all();
        $prefix = $meta['route_prefix'];

        return [
            'id' => $event->id,
            'titre' => $event->titre,
            'categorie' => $event->categorie,
            'categories' => $event->categories_meta,
            'label' => $event->categories_label,
            'color' => $catMeta['color'],
            'text' => $catMeta['text'],
            'type_contenu' => $event->type_contenu,
            'booster' => (bool) $event->booster,
            'urgent' => (bool) $event->urgent,
            'date_debut' => $event->date_debut->toDateString(),
            'date_fin' => ($event->date_fin ?? $event->date_debut)->toDateString(),
            'statut' => $event->statut,
            'valide' => (bool) $event->valide,
            'texte_publication' => $event->texte_publication,
            'texte_publication_linkedin' => $event->texte_publication_linkedin,
            'visuels' => $visuels,
            'visuel_url' => $visuels[0]['url'] ?? $event->visuel_url,
            'visuel_nom' => $visuels[0]['nom'] ?? $event->visuel_nom,
            'reel' => $event->reel_path ? [
                'url' => $event->reel_url,
                'nom' => $event->reel_nom ?: 'Reel',
                'kind' => 'video',
            ] : null,
            'update_url' => route($prefix.'.update', $event),
            'delete_url' => route($prefix.'.destroy', $event),
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
