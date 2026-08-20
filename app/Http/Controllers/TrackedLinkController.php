<?php

namespace App\Http\Controllers;

use App\Models\TrackedLink;
use App\Models\TrackedLinkVisit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TrackedLinkController extends Controller
{
    public function index()
    {
        $links = TrackedLink::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('suivi-liens.index', compact('links'));
    }

    public function create()
    {
        return view('suivi-liens.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateLink($request);

        $slug = filled($validated['slug'] ?? null)
            ? Str::slug($validated['slug'])
            : TrackedLink::generateUniqueSlug();

        if ($slug === '') {
            return back()->withInput()->withErrors(['slug' => 'Le slug est invalide.']);
        }

        if (TrackedLink::where('slug', $slug)->exists()) {
            return back()->withInput()->withErrors(['slug' => 'Ce slug est déjà utilisé.']);
        }

        $link = TrackedLink::create([
            'user_id' => auth()->id(),
            'nom' => $validated['nom'],
            'destination_url' => $validated['destination_url'],
            'slug' => $slug,
            'actif' => true,
        ]);

        app(ActivityLogger::class)->log(
            'suivi_lien',
            auth()->user()->name.' a créé le lien « '.$link->nom.' »',
            auth()->user(),
            'create',
            'Suivi de lien',
            route('suivi-liens.show', $link),
            $link
        );

        return redirect()->route('suivi-liens.show', $link)
            ->with('success', 'Lien de suivi créé.');
    }

    public function show(TrackedLink $suivi_lien)
    {
        $link = $suivi_lien;

        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();

        $clicksTotal = (int) $link->clicks_count;
        $clicksToday = TrackedLinkVisit::where('tracked_link_id', $link->id)
            ->where('created_at', '>=', $todayStart)
            ->count();
        $clicksWeek = TrackedLinkVisit::where('tracked_link_id', $link->id)
            ->where('created_at', '>=', $weekStart)
            ->count();

        $from = $now->copy()->subDays(29)->startOfDay();
        $dailyRaw = TrackedLinkVisit::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('tracked_link_id', $link->id)
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->pluck('total', 'day');

        $chartLabels = [];
        $chartSeries = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->format('Y-m-d');
            $chartLabels[] = $now->copy()->subDays($i)->format('d/m');
            $chartSeries[] = (int) ($dailyRaw[$d] ?? 0);
        }

        $byDevice = TrackedLinkVisit::query()
            ->select('device', DB::raw('COUNT(*) as total'))
            ->where('tracked_link_id', $link->id)
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderByDesc('total')
            ->pluck('total', 'device');

        $byBrowser = TrackedLinkVisit::query()
            ->select('browser', DB::raw('COUNT(*) as total'))
            ->where('tracked_link_id', $link->id)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->pluck('total', 'browser');

        $visits = TrackedLinkVisit::where('tracked_link_id', $link->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('suivi-liens.show', compact(
            'link',
            'clicksTotal',
            'clicksToday',
            'clicksWeek',
            'chartLabels',
            'chartSeries',
            'byDevice',
            'byBrowser',
            'visits'
        ));
    }

    public function edit(TrackedLink $suivi_lien)
    {
        return view('suivi-liens.edit', ['link' => $suivi_lien]);
    }

    public function update(Request $request, TrackedLink $suivi_lien)
    {
        $validated = $this->validateLink($request, $suivi_lien);

        $slug = filled($validated['slug'] ?? null)
            ? Str::slug($validated['slug'])
            : $suivi_lien->slug;

        if ($slug === '') {
            return back()->withInput()->withErrors(['slug' => 'Le slug est invalide.']);
        }

        if (TrackedLink::where('slug', $slug)->where('id', '!=', $suivi_lien->id)->exists()) {
            return back()->withInput()->withErrors(['slug' => 'Ce slug est déjà utilisé.']);
        }

        $suivi_lien->update([
            'nom' => $validated['nom'],
            'destination_url' => $validated['destination_url'],
            'slug' => $slug,
            'actif' => $request->boolean('actif', true),
        ]);

        app(ActivityLogger::class)->log(
            'suivi_lien',
            auth()->user()->name.' a modifié le lien « '.$suivi_lien->nom.' »',
            auth()->user(),
            'update',
            'Suivi de lien',
            route('suivi-liens.show', $suivi_lien),
            $suivi_lien
        );

        return redirect()->route('suivi-liens.show', $suivi_lien)
            ->with('success', 'Lien de suivi mis à jour.');
    }

    public function destroy(TrackedLink $suivi_lien)
    {
        $nom = $suivi_lien->nom;
        $suivi_lien->delete();

        app(ActivityLogger::class)->log(
            'suivi_lien',
            auth()->user()->name.' a supprimé le lien « '.$nom.' »',
            auth()->user(),
            'delete',
            'Suivi de lien',
            route('suivi-liens.index')
        );

        return redirect()->route('suivi-liens.index')
            ->with('success', 'Lien de suivi supprimé.');
    }

    private function validateLink(Request $request, ?TrackedLink $link = null): array
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);
        }

        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'destination_url' => ['required', 'url', 'max:2048'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tracked_links', 'slug')->ignore($link?->id),
            ],
            'actif' => ['sometimes', 'boolean'],
        ], [
            'destination_url.url' => 'L’URL de destination n’est pas valide.',
            'slug.unique' => 'Ce slug est déjà utilisé.',
            'slug.regex' => 'Le slug ne peut contenir que des lettres, chiffres et tirets.',
        ]);
    }
}
