<?php

namespace App\Http\Controllers;

use App\Models\CrmCandidate;
use App\Models\CrmNote;
use App\Models\EcoleAssiduite;
use App\Models\EcoleEtudiant;
use App\Models\EcoleGroupe;
use App\Models\EcoleSeance;
use App\Services\CrmActivityLogger;
use App\Support\AnneeUniversitaire;
use App\Support\EcoleTimetable;
use App\Support\Periode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EcoleController extends Controller
{
    public function dashboard(Request $request)
    {
        [$annee, $mois] = Periode::remember($request);
        $groupes = EcoleGroupe::withCount('etudiants')->orderBy('nom')->get();
        $groupe = $this->selectedGroupe($request, $groupes);
        $monday = $this->monday($request);
        $friday = $monday->copy()->addDays(4);
        $counts = Periode::whereMonth(EcoleAssiduite::query(), 'date')
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');
        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $retard = (int) ($counts['retard'] ?? 0);
        $pointages = $present + $absent + $retard;
        $moisLabel = Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY');

        return view('ecole.dashboard', [
            'title' => 'Dashboard',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'annee' => $annee,
            'mois' => $mois,
            'moisLabel' => $moisLabel,
            'nbEtudiants' => EcoleEtudiant::count(),
            'nbGroupes' => $groupes->count(),
            'groupes' => $groupes,
            'groupe' => $groupe,
            'present' => $present,
            'absent' => $absent,
            'retard' => $retard,
            'pctPresence' => $pointages > 0 ? (int) round(($present / $pointages) * 100) : 0,
            'pctAbsence' => $pointages > 0 ? (int) round(($absent / $pointages) * 100) : 0,
            'presenceLabel' => $this->compte($present, 'présence', 'présences').' en '.$moisLabel,
            'absenceLabel' => $this->compte($absent, 'absence', 'absences').' en '.$moisLabel
                .($retard > 0 ? ' · '.$this->compte($retard, 'retard', 'retards') : ''),
            'monday' => $monday,
            'semaineLabel' => $monday->isoFormat('D MMM').' – '.$friday->isoFormat('D MMM YYYY'),
            'weekNumber' => $monday->isoWeek(),
            'hours' => EcoleTimetable::hours(),
            'columns' => EcoleTimetable::columns(
                $monday,
                $groupe ? $groupe->seances()->orderBy('heure_debut')->get() : collect()
            ),
            'nowTop' => EcoleTimetable::nowTop($monday),
            'prevUrl' => $this->dashboardUrl($groupe, $monday->copy()->subWeek()),
            'nextUrl' => $this->dashboardUrl($groupe, $monday->copy()->addWeek()),
            'todayUrl' => $this->dashboardUrl($groupe, EcoleTimetable::monday()),
        ]);
    }

    public function etudiants(Request $request)
    {
        $this->bootPeriode($request);

        return view('ecole.etudiants', [
            'title' => 'Gestion Etudiants',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'groupes' => EcoleGroupe::orderBy('nom')->get(),
            'etudiants' => EcoleEtudiant::with('groupe')->orderBy('nom')->orderBy('prenom')->get(),
        ]);
    }

    public function searchCrm(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $linkedIds = EcoleEtudiant::query()
            ->whereNotNull('crm_candidate_id')
            ->pluck('crm_candidate_id');

        $candidates = CrmCandidate::query()
            ->where('statut', 'inscrit')
            ->where(function ($query) {
                $query->where('abandon', false)->orWhereNull('abandon');
            })
            ->when($linkedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $linkedIds))
            ->when($term !== '', fn ($query) => $query->searchNameOrPhone($term))
            ->orderBy('nom')
            ->orderBy('prenom')
            ->limit(12)
            ->get(['id', 'prenom', 'nom', 'programme', 'telephone']);

        return response()->json($candidates->map(fn (CrmCandidate $candidate) => [
            'id' => $candidate->id,
            'prenom' => $candidate->prenom,
            'nom' => $candidate->nom,
            'label' => $candidate->full_name,
            'programme' => $candidate->programme,
        ])->values());
    }

    public function storeEtudiant(Request $request, CrmActivityLogger $crmLog)
    {
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'ecole_groupe_id' => ['required', 'integer'],
            'crm_candidate_id' => ['nullable', 'integer'],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'ecole_groupe_id.required' => 'Choisissez un groupe.',
        ]);

        $groupe = EcoleGroupe::findOrFail($data['ecole_groupe_id']);
        $candidate = null;
        if (! empty($data['crm_candidate_id'])) {
            $candidate = CrmCandidate::query()
                ->where('statut', 'inscrit')
                ->where(function ($query) {
                    $query->where('abandon', false)->orWhereNull('abandon');
                })
                ->findOrFail($data['crm_candidate_id']);

            $already = EcoleEtudiant::query()->where('crm_candidate_id', $candidate->id)->exists();
            if ($already) {
                return back()
                    ->withErrors(['crm_candidate_id' => 'Ce candidat est déjà lié à un groupe pour cette année universitaire.'])
                    ->withInput();
            }
        }

        $etudiant = EcoleEtudiant::create([
            'ecole_groupe_id' => $groupe->id,
            'crm_candidate_id' => $candidate?->id,
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
        ]);

        if ($candidate) {
            $phrase = $etudiant->inscriptionLabel();
            $alreadyNoted = $candidate->crmNotes()->where('content', $phrase)->exists();
            if (! $alreadyNoted) {
                $candidate->crmNotes()->create([
                    'user_id' => $request->user()->id,
                    'content' => $phrase,
                ]);
                $crmLog->noteAdded($candidate, $request->user());
            }
            $candidate->touchInteraction();
        }

        return redirect()->route('ecole.etudiants.index')->with('success', 'Étudiant ajouté.');
    }

    public function destroyEtudiant(EcoleEtudiant $etudiant)
    {
        $groupeNom = $etudiant->groupe?->nom;
        $phrase = $groupeNom ? $etudiant->inscriptionLabel() : null;
        $candidateId = $etudiant->crm_candidate_id;
        $etudiant->delete();

        if ($candidateId && $phrase && $groupeNom) {
            $encoreInscrit = EcoleEtudiant::withoutGlobalScopes(['departement', 'annee'])
                ->where('crm_candidate_id', $candidateId)
                ->whereHas('groupe', function ($query) use ($groupeNom) {
                    $query->withoutGlobalScopes(['departement', 'annee'])->where('nom', $groupeNom);
                })
                ->exists();

            if (! $encoreInscrit) {
                CrmNote::query()
                    ->where('crm_candidate_id', $candidateId)
                    ->where('content', $phrase)
                    ->delete();
            }
        }

        return redirect()->route('ecole.etudiants.index')->with('success', 'Étudiant supprimé.');
    }

    public function classes(Request $request)
    {
        $this->bootPeriode($request);

        return $this->page('Gestion Classe', 'Aucune classe pour le moment.');
    }

    public function groupes(Request $request)
    {
        $this->bootPeriode($request);

        return view('ecole.groupes', [
            'title' => 'Gestion Groupe',
            'subtitle' => 'Gestion École',
            'groupes' => EcoleGroupe::withCount('etudiants')->orderBy('nom')->get(),
        ]);
    }

    public function storeGroupe(Request $request)
    {
        $departementId = $request->user()->currentDepartementId();
        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ecole_groupes', 'nom')->where(fn ($query) => $query
                    ->where('departement_id', $departementId)
                    ->where('annee_universitaire', AnneeUniversitaire::selected())),
            ],
        ], [
            'nom.required' => 'Le nom du groupe est obligatoire.',
            'nom.unique' => 'Ce groupe existe déjà.',
        ]);

        EcoleGroupe::create(['nom' => trim($data['nom'])]);

        return redirect()->route('ecole.groupes.index')->with('success', 'Groupe ajouté.');
    }

    public function destroyGroupe(EcoleGroupe $groupe)
    {
        if ($groupe->etudiants()->exists()) {
            return back()->with('error', 'Ce groupe contient encore des étudiants.');
        }

        $groupe->delete();

        return redirect()->route('ecole.groupes.index')->with('success', 'Groupe supprimé.');
    }

    public function emplois(Request $request)
    {
        $this->bootPeriode($request);
        $groupes = EcoleGroupe::orderBy('nom')->get();
        $groupe = $this->selectedGroupe($request, $groupes);

        return view('ecole.emplois', [
            'title' => 'Emplois du temps',
            'subtitle' => 'Gestion École',
            'groupes' => $groupes,
            'groupe' => $groupe,
            'jours' => EcoleTimetable::days(),
            'seances' => $groupe
                ? $groupe->seances()->orderBy('jour')->orderBy('heure_debut')->get()
                : collect(),
        ]);
    }

    public function storeSeance(Request $request)
    {
        $data = $request->validate([
            'ecole_groupe_id' => ['required', 'integer'],
            'jour' => ['required', 'integer', Rule::in(array_keys(EcoleTimetable::days()))],
            'heure_debut' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'heure_fin' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'matiere' => ['required', 'string', 'max:120'],
            'salle' => ['nullable', 'string', 'max:120'],
        ], [
            'ecole_groupe_id.required' => 'Choisissez un groupe.',
            'jour.required' => 'Choisissez un jour.',
            'matiere.required' => 'La matière est obligatoire.',
        ]);

        $groupe = EcoleGroupe::findOrFail($data['ecole_groupe_id']);
        $debut = substr($data['heure_debut'], 0, 5);
        $fin = substr($data['heure_fin'], 0, 5);
        if ($message = $this->creneauInvalide($debut, $fin)) {
            return back()->withErrors(['heure_debut' => $message])->withInput();
        }

        EcoleSeance::create([
            'ecole_groupe_id' => $groupe->id,
            'jour' => (int) $data['jour'],
            'heure_debut' => $debut,
            'heure_fin' => $fin,
            'matiere' => trim($data['matiere']),
            'salle' => trim((string) ($data['salle'] ?? '')) ?: null,
        ]);

        return redirect()
            ->route('ecole.emplois.index', ['groupe' => $groupe->id])
            ->with('success', 'Créneau ajouté à l’emploi du temps.');
    }

    public function destroySeance(EcoleSeance $seance)
    {
        $groupeId = $seance->ecole_groupe_id;
        $seance->delete();

        return redirect()
            ->route('ecole.emplois.index', ['groupe' => $groupeId])
            ->with('success', 'Créneau supprimé.');
    }

    public function absences(Request $request)
    {
        [$annee, $mois] = Periode::remember($request);

        $dateParDefaut = now()->year === $annee && now()->month === $mois
            ? now()->toDateString()
            : Carbon::create($annee, $mois, 1)->toDateString();

        return view('ecole.absences', [
            'title' => 'Retard et Absence',
            'subtitle' => 'Gestion École',
            'annee' => $annee,
            'mois' => $mois,
            'dateParDefaut' => $dateParDefaut,
            'etudiants' => EcoleEtudiant::with('groupe')->orderBy('nom')->orderBy('prenom')->get(),
            'statuts' => EcoleAssiduite::STATUTS,
            'assiduites' => Periode::whereMonth(EcoleAssiduite::with('etudiant.groupe'), 'date')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function storeAbsence(Request $request)
    {
        $data = $request->validate([
            'ecole_etudiant_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'statut' => ['required', Rule::in(array_keys(EcoleAssiduite::STATUTS))],
        ], [
            'ecole_etudiant_id.required' => 'Choisissez un étudiant.',
            'date.required' => 'La date est obligatoire.',
            'statut.required' => 'Choisissez un statut.',
        ]);

        $etudiant = EcoleEtudiant::findOrFail($data['ecole_etudiant_id']);
        EcoleAssiduite::updateOrCreate(
            [
                'ecole_etudiant_id' => $etudiant->id,
                'date' => $data['date'],
            ],
            ['statut' => $data['statut']]
        );

        $date = Carbon::parse($data['date']);

        return redirect()
            ->route('ecole.absences.index', ['annee' => $date->year, 'mois' => $date->month])
            ->with('success', 'Pointage enregistré.');
    }

    public function destroyAbsence(EcoleAssiduite $assiduite)
    {
        $assiduite->delete();

        return redirect()->route('ecole.absences.index')->with('success', 'Pointage supprimé.');
    }

    public function bulletins(Request $request)
    {
        $this->bootPeriode($request);

        return $this->page('Bulletin de note', 'Aucun bulletin pour le moment.');
    }

    public function annee(Request $request)
    {
        $data = $request->validate([
            'annee_universitaire' => ['required', 'string', Rule::in(AnneeUniversitaire::options())],
        ], [
            'annee_universitaire.in' => 'Choisissez l’année universitaire en cours ou une année à venir.',
        ]);

        AnneeUniversitaire::select($data['annee_universitaire']);

        return back();
    }

    private function page(string $title, string $empty)
    {
        return view('ecole.index', [
            'title' => $title,
            'subtitle' => 'Gestion École',
            'empty' => $empty,
        ]);
    }

    private function bootPeriode(Request $request): void
    {
        Periode::remember($request);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EcoleGroupe>  $groupes
     */
    private function selectedGroupe(Request $request, $groupes): ?EcoleGroupe
    {
        $groupe = $groupes->firstWhere('id', (int) $request->query('groupe'));

        return $groupe ?: $groupes->first();
    }

    private function monday(Request $request): Carbon
    {
        $value = $request->query('semaine');
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return EcoleTimetable::monday();
        }

        try {
            return EcoleTimetable::monday($value);
        } catch (\Throwable) {
            return EcoleTimetable::monday();
        }
    }

    private function dashboardUrl(?EcoleGroupe $groupe, Carbon $monday): string
    {
        return route('ecole.dashboard', array_filter([
            'groupe' => $groupe?->id,
            'semaine' => $monday->toDateString(),
        ]));
    }

    private function creneauInvalide(string $debut, string $fin): ?string
    {
        $start = EcoleTimetable::minutes($debut);
        $end = EcoleTimetable::minutes($fin);
        if ($start < EcoleTimetable::START * 60 || $end > EcoleTimetable::END * 60 || $end <= $start) {
            return 'Le créneau doit commencer à 08h ou après, et se terminer à 17h au plus tard.';
        }

        return null;
    }

    private function compte(int $nombre, string $singulier, string $pluriel): string
    {
        return $nombre.' '.($nombre > 1 ? $pluriel : $singulier);
    }
}
