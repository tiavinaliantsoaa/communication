<?php

namespace App\Http\Controllers;

use App\Models\CrmCandidate;
use App\Models\CrmNote;
use App\Models\EcoleAssiduite;
use App\Models\EcoleEnseignant;
use App\Models\EcoleEtudiant;
use App\Models\EcoleGroupe;
use App\Models\EcoleMatiere;
use App\Models\EcoleSalle;
use App\Models\EcoleSeance;
use App\Models\Permission;
use App\Models\User;
use App\Services\CrmActivityLogger;
use App\Support\AnneeUniversitaire;
use App\Support\EcoleTimetable;
use App\Support\Periode;
use App\Support\VueCompte;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EcoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (! $user || ! $user->isParent()) {
                return $next($request);
            }

            $autorisees = [
                'ecole.dashboard',
                'ecole.etudiants.show',
                'ecole.emplois.index',
                'ecole.absences.index',
            ];

            if (! in_array($request->route()?->getName(), $autorisees, true)) {
                abort(403, 'Cette page n’est pas disponible pour un compte parent.');
            }

            return $next($request);
        });

        $this->middleware(function (Request $request, $next) {
            if (VueCompte::current() && ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                abort(403, 'Ce tableau de bord est en lecture seule.');
            }

            return $next($request);
        });
    }

    public function dashboard(Request $request)
    {
        if ($request->user()?->isEnseignant()) {
            return $this->rendreDashboard($request, $request->user()->ficheEnseignant ?? new EcoleEnseignant);
        }
        if ($request->user()?->isEtudiant()) {
            return $this->rendreDashboard($request, null, collect([new EcoleEtudiant]));
        }

        $vue = VueCompte::current();
        if ($vue && $vue['type'] === 'enseignant') {
            return $this->rendreDashboard($request, VueCompte::enseignant());
        }
        if ($vue && in_array($vue['type'], ['etudiant', 'parent'], true)) {
            return $this->rendreDashboard($request, null, VueCompte::etudiants());
        }

        return $this->rendreDashboard($request);
    }

    public function compteEnseignant(Request $request)
    {
        $enseignants = EcoleEnseignant::orderBy('nom')->orderBy('prenom')->get();
        $enseignant = $enseignants->firstWhere('id', (int) $request->query('enseignant'))
            ?: VueCompte::enseignant();

        if ($enseignant) {
            return $this->rendreDashboard($request, $enseignant, null, 'ecole.compte-enseignant', [
                'enseignant' => $enseignant->id,
            ]);
        }

        return view('ecole.compte-enseignant', [
            'title' => 'Compte enseignant',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'enseignants' => $enseignants,
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
        $compteParent = $request->boolean('creer_compte_parent');
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'ecole_groupe_id' => ['required', 'integer'],
            'crm_candidate_id' => ['nullable', 'integer'],
            'creer_compte_parent' => ['nullable', 'boolean'],
            'parent_nom' => [Rule::requiredIf($compteParent), 'nullable', 'string', 'max:120'],
            'parent_email' => [Rule::requiredIf($compteParent), 'nullable', 'email', 'max:255'],
            'parent_password' => [Rule::requiredIf($compteParent), 'nullable', 'string', 'min:8', 'max:100'],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'ecole_groupe_id.required' => 'Choisissez un groupe.',
            'parent_nom.required' => 'Le nom du parent est obligatoire.',
            'parent_email.required' => 'L’email du parent est obligatoire.',
            'parent_email.email' => 'L’email du parent n’est pas valide.',
            'parent_password.required' => 'Le mot de passe du parent est obligatoire.',
            'parent_password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
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

        $compteCree = false;
        $etudiant = DB::transaction(function () use ($request, $data, $groupe, $candidate, $compteParent, &$compteCree) {
            $parent = $compteParent ? $this->compteParent($request, $data, $compteCree) : null;

            return EcoleEtudiant::create([
                'ecole_groupe_id' => $groupe->id,
                'crm_candidate_id' => $candidate?->id,
                'parent_user_id' => $parent?->id,
                'prenom' => trim($data['prenom']),
                'nom' => trim($data['nom']),
                'palier' => $candidate ? $this->vide($candidate->niveau_etudes ?: $candidate->programme) : null,
                'date_naissance' => $candidate?->date_naissance,
                'email' => $candidate ? $this->vide($candidate->email) : null,
                'domicile' => $candidate ? $this->vide($candidate->adresse) : null,
            ]);
        });

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

        $message = 'Étudiant ajouté.';
        if ($compteParent) {
            $message = $compteCree
                ? 'Étudiant ajouté. Compte parent créé.'
                : 'Étudiant ajouté. Compte parent associé.';
        }

        return redirect()->route('ecole.etudiants.index')->with('success', $message);
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

    public function showEtudiant(Request $request, EcoleEtudiant $etudiant)
    {
        $this->bootPeriode($request);
        $etudiant->load(['groupe', 'candidat']);
        $groupe = $etudiant->groupe;
        $monday = $this->monday($request);
        $friday = $monday->copy()->addDays(4);

        return view('ecole.etudiant', [
            'title' => $etudiant->nom_complet,
            'subtitle' => 'Gestion Groupe'.($groupe ? ' — '.$groupe->nom : ''),
            'etudiant' => $etudiant,
            'peutModifier' => $request->user()->peutModifierFicheEleve(),
            'groupe' => $groupe,
            'monday' => $monday,
            'semaineLabel' => $monday->isoFormat('D MMM').' – '.$friday->isoFormat('D MMM YYYY'),
            'weekNumber' => $monday->isoWeek(),
            'hours' => EcoleTimetable::hours(),
            'columns' => EcoleTimetable::columns($monday, $groupe ? $this->seancesDeLaSemaine($groupe, $monday) : collect()),
            'nowTop' => EcoleTimetable::nowTop($monday),
            'prevUrl' => route('ecole.etudiants.show', ['etudiant' => $etudiant, 'semaine' => $monday->copy()->subWeek()->toDateString()]),
            'nextUrl' => route('ecole.etudiants.show', ['etudiant' => $etudiant, 'semaine' => $monday->copy()->addWeek()->toDateString()]),
            'todayUrl' => route('ecole.etudiants.show', $etudiant),
        ]);
    }

    public function editEtudiant(Request $request, EcoleEtudiant $etudiant)
    {
        $this->refuserHorsEquipe($request);
        $this->bootPeriode($request);
        $etudiant->load(['groupe', 'candidat']);

        return view('ecole.etudiant-edit', [
            'title' => 'Modifier '.$etudiant->nom_complet,
            'subtitle' => 'Gestion Groupe',
            'etudiant' => $etudiant,
            'groupes' => EcoleGroupe::orderBy('nom')->get(),
        ]);
    }

    public function updateEtudiant(Request $request, EcoleEtudiant $etudiant)
    {
        $this->refuserHorsEquipe($request);
        $departementId = $request->user()->currentDepartementId();
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'nom_officiel' => ['nullable', 'string', 'max:150'],
            'nom_caracteres' => ['nullable', 'string', 'max:150'],
            'palier' => ['nullable', 'string', 'max:80'],
            'ecole_groupe_id' => [
                'required',
                'integer',
                Rule::exists('ecole_groupes', 'id')->where(fn ($query) => $query
                    ->where('departement_id', $departementId)
                    ->where('annee_universitaire', AnneeUniversitaire::selected())),
            ],
            'nom_utilisateur' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('ecole_etudiants', 'nom_utilisateur')->where(fn ($query) => $query->where('departement_id', $departementId))->ignore($etudiant->id),
            ],
            'date_naissance' => ['nullable', 'date', 'before_or_equal:today'],
            'site_web' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'code_verrouillage' => ['nullable', 'string', 'max:40'],
            'matricule' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('ecole_etudiants', 'matricule')->where(fn ($query) => $query->where('departement_id', $departementId))->ignore($etudiant->id),
            ],
            'domicile' => ['nullable', 'string', 'max:255'],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'ecole_groupe_id.required' => 'Choisissez un groupe.',
            'ecole_groupe_id.exists' => 'Choisissez un groupe.',
            'email.email' => 'Le courriel n’est pas valide.',
            'date_naissance.before_or_equal' => 'La date de naissance ne peut pas être dans le futur.',
            'nom_utilisateur.unique' => 'Ce nom d’utilisateur est déjà utilisé.',
            'matricule.unique' => 'Ce matricule est déjà utilisé.',
        ]);

        $etudiant->update([
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
            'nom_officiel' => $this->vide($data['nom_officiel'] ?? null),
            'nom_caracteres' => $this->vide($data['nom_caracteres'] ?? null),
            'palier' => $this->vide($data['palier'] ?? null),
            'ecole_groupe_id' => $data['ecole_groupe_id'],
            'nom_utilisateur' => $this->vide($data['nom_utilisateur'] ?? null),
            'date_naissance' => $data['date_naissance'] ?? null,
            'site_web' => $this->vide($data['site_web'] ?? null),
            'email' => $this->vide($data['email'] ?? null),
            'code_verrouillage' => $this->vide($data['code_verrouillage'] ?? null),
            'matricule' => $this->vide($data['matricule'] ?? null),
            'domicile' => $this->vide($data['domicile'] ?? null),
        ]);

        return redirect()->route('ecole.etudiants.show', $etudiant)->with('success', 'Fiche élève mise à jour.');
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

    public function showGroupe(Request $request, EcoleGroupe $groupe)
    {
        $this->bootPeriode($request);

        return view('ecole.groupe-etudiants', [
            'title' => $groupe->nom,
            'subtitle' => 'Gestion Groupe',
            'groupe' => $groupe,
            'etudiants' => $groupe->etudiants()->orderBy('nom')->orderBy('prenom')->paginate(10)->withQueryString(),
        ]);
    }

    public function exportGroupe(EcoleGroupe $groupe)
    {
        $etudiants = $groupe->etudiants()->orderBy('nom')->orderBy('prenom')->get();
        $filename = 'etudiants-'.Str::slug($groupe->nom).'.csv';

        return response()->streamDownload(function () use ($etudiants, $groupe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Prénom', 'Nom', 'Groupe'], ';');
            foreach ($etudiants as $etudiant) {
                fputcsv($out, [$etudiant->prenom, $etudiant->nom, $groupe->nom], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storePresenceGroupe(EcoleGroupe $groupe)
    {
        $etudiants = $groupe->etudiants()->get();
        if ($etudiants->isEmpty()) {
            return back()->with('error', 'Ce groupe ne contient aucun étudiant.');
        }

        $date = now()->toDateString();
        foreach ($etudiants as $etudiant) {
            EcoleAssiduite::firstOrCreate(
                [
                    'ecole_etudiant_id' => $etudiant->id,
                    'date' => $date,
                ],
                ['statut' => 'present']
            );
        }

        $nombre = $etudiants->count();

        return redirect()
            ->route('ecole.groupes.show', $groupe)
            ->with('success', 'Présence enregistrée pour '.$nombre.' '.($nombre > 1 ? 'étudiants' : 'étudiant').'.');
    }

    public function matieres(Request $request)
    {
        $this->bootPeriode($request);

        return view('ecole.matieres', [
            'title' => 'Matière',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'matieres' => EcoleMatiere::orderBy('nom')->get(),
        ]);
    }

    public function storeMatiere(Request $request)
    {
        $departementId = $request->user()->currentDepartementId();
        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:120',
                Rule::unique('ecole_matieres', 'nom')->where(fn ($query) => $query->where('departement_id', $departementId)),
            ],
        ], [
            'nom.required' => 'Le nom de la matière est obligatoire.',
            'nom.unique' => 'Cette matière existe déjà.',
        ]);

        EcoleMatiere::create(['nom' => trim($data['nom'])]);

        return redirect()->route('ecole.matieres.index')->with('success', 'Matière ajoutée.');
    }

    public function destroyMatiere(EcoleMatiere $matiere)
    {
        $matiere->delete();

        return redirect()->route('ecole.matieres.index')->with('success', 'Matière supprimée.');
    }

    public function enseignants(Request $request)
    {
        $this->bootPeriode($request);

        return view('ecole.enseignants', [
            'title' => 'Enseignant',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'enseignants' => EcoleEnseignant::orderBy('nom')->orderBy('prenom')->get(),
        ]);
    }

    public function storeEnseignant(Request $request)
    {
        $compte = $request->boolean('creer_compte_enseignant');
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:80'],
            'nom' => ['required', 'string', 'max:80'],
            'creer_compte_enseignant' => ['nullable', 'boolean'],
            'enseignant_email' => [Rule::requiredIf($compte), 'nullable', 'email', 'max:255'],
            'enseignant_password' => [Rule::requiredIf($compte), 'nullable', 'string', 'min:8', 'max:100'],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'enseignant_email.required' => 'L’email de l’enseignant est obligatoire.',
            'enseignant_email.email' => 'L’email de l’enseignant n’est pas valide.',
            'enseignant_password.required' => 'Le mot de passe de l’enseignant est obligatoire.',
            'enseignant_password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $compteCree = false;
        DB::transaction(function () use ($request, $data, $compte, &$compteCree) {
            $user = $compte ? $this->compteEnseignantUtilisateur($request, $data, $compteCree) : null;
            EcoleEnseignant::create([
                'prenom' => trim($data['prenom']),
                'nom' => trim($data['nom']),
                'user_id' => $user?->id,
            ]);
        });

        $message = 'Enseignant ajouté.';
        if ($compte) {
            $message = $compteCree
                ? 'Enseignant ajouté. Compte enseignant créé.'
                : 'Enseignant ajouté. Compte enseignant associé.';
        }

        return redirect()->route('ecole.enseignants.index')->with('success', $message);
    }

    public function destroyEnseignant(EcoleEnseignant $enseignant)
    {
        $enseignant->delete();

        return redirect()->route('ecole.enseignants.index')->with('success', 'Enseignant supprimé.');
    }

    public function emplois(Request $request)
    {
        $this->bootPeriode($request);
        $groupes = EcoleGroupe::orderBy('nom')->get();
        $groupe = $this->selectedGroupe($request, $groupes);
        $monday = $this->monday($request);
        $friday = $monday->copy()->addDays(4);
        $today = now()->startOfDay();
        $dateParDefaut = old('date', $today->between($monday, $friday) && $today->isoWeekday() <= 5
            ? $today->toDateString()
            : $monday->toDateString());
        $seances = $this->seancesCalendrier($monday);
        $ficheEnseignant = $request->user()?->isEnseignant() ? $request->user()->ficheEnseignant : null;
        $vue = VueCompte::current();
        $apercuEnseignant = $request->query('apercu') === 'enseignant'
            ? $this->identifiant($request->query('enseignant'))
            : null;
        if ($ficheEnseignant) {
            $seances = $seances->where('ecole_enseignant_id', $ficheEnseignant->id)->values();
            $groupes = $groupes->whereIn('id', $seances->pluck('ecole_groupe_id')->unique()->all())->values();
            $groupe = $groupes->first();
        } elseif ($vue && $vue['type'] === 'enseignant') {
            $seances = $seances->where('ecole_enseignant_id', $vue['id'])->values();
            $groupes = $groupes->whereIn('id', $seances->pluck('ecole_groupe_id')->unique()->all())->values();
            $groupe = $groupes->first();
        } elseif ($apercuEnseignant) {
            $seances = $seances->where('ecole_enseignant_id', $apercuEnseignant)->values();
            $groupes = $groupes->whereIn('id', $seances->pluck('ecole_groupe_id')->unique()->all())->values();
            $groupe = $groupes->first();
        } elseif ($vue && in_array($vue['type'], ['etudiant', 'parent'], true)) {
            $groupeIds = VueCompte::etudiants()->pluck('ecole_groupe_id')->filter()->all();
            $seances = $seances->whereIn('ecole_groupe_id', $groupeIds)->values();
            $groupes = $groupes->whereIn('id', $groupeIds)->values();
            $groupe = $this->selectedGroupe($request, $groupes);
        }
        $semaineLabel = $monday->isoFormat('D MMM').' – '.$friday->isoFormat('D MMM YYYY');
        $lectureParent = (bool) $request->user()?->isCompteEcole() || $vue !== null || $apercuEnseignant !== null;
        $matieres = EcoleMatiere::orderBy('nom')->get();
        $enseignants = EcoleEnseignant::orderBy('nom')->orderBy('prenom')->get();
        $salles = EcoleSalle::orderBy('nom')->get();
        $enseignantId = $this->identifiant(old('ecole_enseignant_id', $request->query('enseignant')));
        $salleId = $this->identifiant(old('ecole_salle_id', $request->query('salle')));
        $peutCreer = ! $lectureParent && $groupes->isNotEmpty() && $matieres->isNotEmpty() && $enseignants->isNotEmpty();
        $apercu = $peutCreer ? [
            'date' => $dateParDefaut,
            'debut' => old('heure_debut', '08:00'),
            'fin' => old('heure_fin', '09:00'),
        ] : null;
        $grille = EcoleTimetable::disponibilites($monday, $seances, $groupe?->id, $enseignantId, $salleId, $apercu);

        if ($request->wantsJson()) {
            return response()->json([
                'monday' => $monday->toDateString(),
                'label' => $semaineLabel,
                'seances' => $this->serialiserSeances($seances),
            ]);
        }

        return view('ecole.emplois', [
            'title' => 'Emplois du temps',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'groupes' => $groupes,
            'groupe' => $groupe,
            'monday' => $monday,
            'semaineLabel' => $semaineLabel,
            'prevUrl' => route('ecole.emplois.index', array_filter([
                'groupe' => $groupe?->id,
                'semaine' => $monday->copy()->subWeek()->toDateString(),
                'enseignant' => $enseignantId,
                'salle' => $salleId,
            ])),
            'nextUrl' => route('ecole.emplois.index', array_filter([
                'groupe' => $groupe?->id,
                'semaine' => $monday->copy()->addWeek()->toDateString(),
                'enseignant' => $enseignantId,
                'salle' => $salleId,
            ])),
            'dateParDefaut' => $dateParDefaut,
            'enseignantId' => $enseignantId,
            'salleId' => $salleId,
            'columns' => $grille['columns'],
            'conflit' => $grille['conflit'],
            'calendrierVide' => $seances->isEmpty(),
            'hours' => EcoleTimetable::hours(),
            'nowTop' => EcoleTimetable::nowTop($monday),
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'salles' => $salles,
            'cal' => [
                'lecture' => $lectureParent,
                'peutCreer' => $peutCreer,
                'groupeId' => $groupe?->id,
                'enseignantId' => $enseignantId,
                'salleId' => $salleId,
                'date' => $dateParDefaut,
                'debut' => old('heure_debut', '08:00'),
                'fin' => old('heure_fin', '09:00'),
                'monday' => $monday->toDateString(),
                'label' => $semaineLabel,
                'seances' => $this->serialiserSeances($seances),
                'indexUrl' => route('ecole.emplois.index'),
                'destroyBase' => url('/ecole/emplois-du-temps'),
                'csrf' => csrf_token(),
                'extra' => array_filter([
                    'apercu' => $apercuEnseignant ? 'enseignant' : null,
                    'enseignant' => ($vue && $vue['type'] === 'enseignant') ? $vue['id'] : $apercuEnseignant,
                ]),
                'groupes' => $groupes->map(fn ($ligne) => ['id' => $ligne->id, 'nom' => $ligne->nom])->values(),
                'conflit' => $grille['conflit'],
                'vide' => $seances->isEmpty(),
            ],
        ]);
    }

    public function storeSeance(Request $request)
    {
        $departementId = $request->user()->currentDepartementId();
        $data = $request->validate([
            'ecole_groupe_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'heure_debut' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'heure_fin' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'ecole_matiere_id' => [
                'required',
                'integer',
                Rule::exists('ecole_matieres', 'id')->where(fn ($query) => $query->where('departement_id', $departementId)),
            ],
            'ecole_enseignant_id' => [
                'required',
                'integer',
                Rule::exists('ecole_enseignants', 'id')->where(fn ($query) => $query->where('departement_id', $departementId)),
            ],
            'ecole_salle_id' => [
                'nullable',
                'integer',
                Rule::exists('ecole_salles', 'id')->where(fn ($query) => $query->where('departement_id', $departementId)),
            ],
        ], [
            'ecole_groupe_id.required' => 'Choisissez un groupe.',
            'date.required' => 'Choisissez une date.',
            'ecole_matiere_id.required' => 'Choisissez une matière.',
            'ecole_matiere_id.exists' => 'Choisissez une matière.',
            'ecole_enseignant_id.required' => 'Choisissez un enseignant.',
            'ecole_enseignant_id.exists' => 'Choisissez un enseignant.',
            'ecole_salle_id.exists' => 'Choisissez une salle de classe.',
        ]);

        $groupe = EcoleGroupe::findOrFail($data['ecole_groupe_id']);
        $matiere = EcoleMatiere::findOrFail($data['ecole_matiere_id']);
        $enseignant = EcoleEnseignant::findOrFail($data['ecole_enseignant_id']);
        $salle = ! empty($data['ecole_salle_id']) ? EcoleSalle::findOrFail($data['ecole_salle_id']) : null;
        $date = Carbon::parse($data['date'])->startOfDay();
        if ($date->isoWeekday() > 5) {
            return back()->withErrors(['date' => 'Choisissez un jour du lundi au vendredi.'])->withInput();
        }

        $debut = substr($data['heure_debut'], 0, 5);
        $fin = substr($data['heure_fin'], 0, 5);
        if ($message = $this->creneauInvalide($debut, $fin)) {
            return back()->withErrors(['heure_debut' => $message])->withInput();
        }

        if ($salle && ($message = $this->salleIncompatible($groupe, $salle))) {
            return back()->withErrors(['ecole_salle_id' => $message])->withInput();
        }

        if ($message = $this->creneauSuperpose($groupe, $enseignant, $salle, $date, $debut, $fin)) {
            return back()->withErrors(['heure_debut' => $message])->withInput();
        }

        EcoleSeance::create([
            'ecole_groupe_id' => $groupe->id,
            'date' => $date->toDateString(),
            'jour' => $date->isoWeekday(),
            'heure_debut' => $debut,
            'heure_fin' => $fin,
            'ecole_matiere_id' => $matiere->id,
            'ecole_enseignant_id' => $enseignant->id,
            'ecole_salle_id' => $salle?->id,
            'matiere' => $matiere->nom,
            'professeur' => $enseignant->nom_complet,
            'salle' => $salle?->nom,
        ]);

        return redirect()
            ->route('ecole.emplois.index', [
                'groupe' => $groupe->id,
                'semaine' => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            ])
            ->with('success', 'Créneau ajouté pour le '.$date->locale('fr')->isoFormat('dddd D MMMM YYYY').'.');
    }

    public function destroySeance(EcoleSeance $seance)
    {
        $groupeId = $seance->ecole_groupe_id;
        $semaine = $seance->date?->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $seance->delete();

        return redirect()
            ->route('ecole.emplois.index', array_filter([
                'groupe' => $groupeId,
                'semaine' => $semaine,
            ]))
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
                ->when(VueCompte::current() && in_array(VueCompte::current()['type'], ['etudiant', 'parent'], true), function ($query) {
                    $query->whereIn('ecole_etudiant_id', VueCompte::etudiants()->pluck('id')->all());
                })
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

    public function parametres(Request $request)
    {
        $this->bootPeriode($request);

        return view('ecole.parametres', [
            'title' => 'Paramètre école',
            'subtitle' => 'Gestion École',
            'annees' => AnneeUniversitaire::options(),
            'selection' => AnneeUniversitaire::selected(),
            'actuelle' => AnneeUniversitaire::current(),
            'salles' => EcoleSalle::orderBy('nom')->get(),
        ]);
    }

    public function storeSalle(Request $request)
    {
        $departementId = $request->user()->currentDepartementId();
        $data = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:120',
                Rule::unique('ecole_salles', 'nom')->where(fn ($query) => $query->where('departement_id', $departementId)),
            ],
            'capacite' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'nom.required' => 'Le nom de la salle est obligatoire.',
            'nom.unique' => 'Cette salle existe déjà.',
            'capacite.required' => 'Indiquez le nombre d’étudiants que la salle peut accueillir.',
            'capacite.integer' => 'Le nombre d’étudiants doit être un nombre entier.',
            'capacite.min' => 'La salle doit pouvoir accueillir au moins un étudiant.',
        ]);

        EcoleSalle::create([
            'nom' => trim($data['nom']),
            'capacite' => $data['capacite'],
        ]);

        return redirect()->route('ecole.parametres.index')->with('success', 'Salle de classe ajoutée.');
    }

    public function destroySalle(EcoleSalle $salle)
    {
        $salle->delete();

        return redirect()->route('ecole.parametres.index')->with('success', 'Salle de classe supprimée.');
    }

    public function annee(Request $request)
    {
        $data = $request->validate([
            'annee_universitaire' => ['required', 'string', Rule::in(AnneeUniversitaire::options())],
        ], [
            'annee_universitaire.in' => 'Choisissez une année universitaire proposée.',
        ]);

        AnneeUniversitaire::select($data['annee_universitaire']);

        return redirect()->route('ecole.parametres.index');
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

    private function rendreDashboard(Request $request, ?EcoleEnseignant $enseignant = null, $etudiants = null, string $route = 'ecole.dashboard', array $extra = [])
    {
        [$annee, $mois] = Periode::remember($request);
        $groupes = EcoleGroupe::withCount('etudiants')->orderBy('nom')->get();
        $etudiants = $etudiants instanceof \Illuminate\Support\Collection ? $etudiants : collect();
        if ($etudiants->isNotEmpty()) {
            $groupes = $groupes->whereIn('id', $etudiants->pluck('ecole_groupe_id')->filter()->all())->values();
        }
        $groupe = $this->selectedGroupe($request, $groupes);
        $monday = $this->monday($request);
        $friday = $monday->copy()->addDays(4);
        $counts = Periode::whereMonth(EcoleAssiduite::query(), 'date')
            ->when($etudiants->isNotEmpty(), fn ($query) => $query->whereIn('ecole_etudiant_id', $etudiants->pluck('id')->all()))
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');
        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $retard = (int) ($counts['retard'] ?? 0);
        $pointages = $present + $absent + $retard;
        $moisLabel = Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY');
        $heuresParent = $request->user()?->isParent()
            ? $this->heuresParent()
            : ($etudiants->isNotEmpty() ? $this->heuresDesEtudiants($etudiants) : null);
        $seances = $enseignant
            ? $this->seancesEnseignant($enseignant, $monday)
            : ($groupe ? $this->seancesDeLaSemaine($groupe, $monday) : collect());
        $lien = array_filter(array_merge($extra, [
            'groupe' => $enseignant ? null : $groupe?->id,
            'semaine' => $monday->toDateString(),
        ]));

        return view('ecole.dashboard', [
            'title' => $enseignant ? 'Compte enseignant' : 'Dashboard',
            'subtitle' => 'Gestion École — '.AnneeUniversitaire::selected(),
            'annee' => $annee,
            'mois' => $mois,
            'moisLabel' => $moisLabel,
            'nbEtudiants' => $etudiants->isNotEmpty() ? $etudiants->count() : EcoleEtudiant::count(),
            'nbGroupes' => $groupes->count(),
            'groupes' => $groupes,
            'groupe' => $groupe,
            'enseignantVu' => $enseignant,
            'present' => $present,
            'absent' => $absent,
            'retard' => $retard,
            'pctPresence' => $pointages > 0 ? (int) round(($present / $pointages) * 100) : 0,
            'pctAbsence' => $pointages > 0 ? (int) round(($absent / $pointages) * 100) : 0,
            'presenceLabel' => $this->compte($present, 'présence', 'présences').' en '.$moisLabel,
            'absenceLabel' => $this->compte($absent, 'absence', 'absences').' en '.$moisLabel
                .($retard > 0 ? ' · '.$this->compte($retard, 'retard', 'retards') : ''),
            'heuresParent' => $heuresParent,
            'monday' => $monday,
            'semaineLabel' => $monday->isoFormat('D MMM').' – '.$friday->isoFormat('D MMM YYYY'),
            'weekNumber' => $monday->isoWeek(),
            'hours' => EcoleTimetable::hours(),
            'columns' => EcoleTimetable::columns($monday, $seances),
            'nowTop' => EcoleTimetable::nowTop($monday),
            'prevUrl' => route($route, array_merge($lien, ['semaine' => $monday->copy()->subWeek()->toDateString()])),
            'nextUrl' => route($route, array_merge($lien, ['semaine' => $monday->copy()->addWeek()->toDateString()])),
            'todayUrl' => route($route, array_merge($lien, ['semaine' => EcoleTimetable::monday()->toDateString()])),
            'lienEmploi' => route('ecole.emplois.index', array_filter([
                'groupe' => $enseignant ? null : $groupe?->id,
                'enseignant' => $enseignant?->id,
                'apercu' => $enseignant ? 'enseignant' : null,
            ])),
        ]);
    }

    private function dashboardUrl(?EcoleGroupe $groupe, Carbon $monday): string
    {
        return route('ecole.dashboard', array_filter([
            'groupe' => $groupe?->id,
            'semaine' => $monday->toDateString(),
        ]));
    }

    private function identifiant(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function seancesCalendrier(Carbon $monday)
    {
        return EcoleSeance::query()
            ->whereHas('groupe')
            ->whereDate('date', '>=', $monday->toDateString())
            ->whereDate('date', '<=', $monday->copy()->addDays(4)->toDateString())
            ->with('groupe')
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();
    }

    private function serialiserSeances($seances): array
    {
        return $seances->map(fn (EcoleSeance $seance) => [
            'id' => $seance->id,
            'date' => $seance->date?->toDateString(),
            'debut' => $seance->heure('heure_debut'),
            'fin' => $seance->heure('heure_fin'),
            'matiere' => $seance->matiere,
            'professeur' => $seance->professeur,
            'salle' => $seance->salle,
            'groupe_id' => (int) $seance->ecole_groupe_id,
            'groupe' => $seance->groupe?->nom,
            'enseignant_id' => $seance->ecole_enseignant_id ? (int) $seance->ecole_enseignant_id : null,
            'salle_id' => $seance->ecole_salle_id ? (int) $seance->ecole_salle_id : null,
        ])->values()->all();
    }

    private function seancesEnseignant(?EcoleEnseignant $enseignant, Carbon $monday)
    {
        if (! $enseignant?->id) {
            return collect();
        }

        return EcoleSeance::query()
            ->where('ecole_enseignant_id', $enseignant->id)
            ->whereDate('date', '>=', $monday->toDateString())
            ->whereDate('date', '<=', $monday->copy()->addDays(4)->toDateString())
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();
    }

    private function seancesDeLaSemaine(?EcoleGroupe $groupe, Carbon $monday)
    {
        if (! $groupe) {
            return collect();
        }

        return $groupe->seances()
            ->whereDate('date', '>=', $monday->toDateString())
            ->whereDate('date', '<=', $monday->copy()->addDays(4)->toDateString())
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();
    }

    private function salleIncompatible(EcoleGroupe $groupe, EcoleSalle $salle): ?string
    {
        $effectif = $groupe->etudiants()->count();
        if ($effectif <= $salle->capacite) {
            return null;
        }

        $eleves = $effectif > 1 ? 'élèves' : 'élève';
        $personnes = $salle->capacite > 1 ? 'personnes' : 'personne';

        return "Incompatibilité : le groupe {$groupe->nom} compte {$effectif} {$eleves}, la salle {$salle->nom} ne peut accueillir que {$salle->capacite} {$personnes}.";
    }

    private function creneauSuperpose(EcoleGroupe $groupe, EcoleEnseignant $enseignant, ?EcoleSalle $salle, Carbon $date, string $debut, string $fin): ?string
    {
        $debutMin = EcoleTimetable::minutes($debut);
        $finMin = EcoleTimetable::minutes($fin);
        $groupeConflit = null;
        $profConflit = null;
        $salleConflit = null;

        $seances = EcoleSeance::query()
            ->whereDate('date', $date->toDateString())
            ->where(function ($query) use ($groupe, $enseignant, $salle) {
                $query->where('ecole_groupe_id', $groupe->id)
                    ->orWhere('ecole_enseignant_id', $enseignant->id);
                if ($salle) {
                    $query->orWhere('ecole_salle_id', $salle->id);
                }
            })
            ->get();

        foreach ($seances as $seance) {
            $start = EcoleTimetable::minutes($seance->heure('heure_debut'));
            $end = EcoleTimetable::minutes($seance->heure('heure_fin'));
            if ($debutMin >= $end || $finMin <= $start) {
                continue;
            }

            $horaire = EcoleTimetable::label($seance->heure('heure_debut')).' – '.EcoleTimetable::label($seance->heure('heure_fin'));
            $detail = $seance->matiere.', '.$horaire;
            if ((int) $seance->ecole_groupe_id === (int) $groupe->id) {
                $groupeConflit ??= $detail;
            }
            if ((int) $seance->ecole_enseignant_id === (int) $enseignant->id) {
                $profConflit ??= $detail;
            }
            if ($salle && (int) $seance->ecole_salle_id === (int) $salle->id) {
                $salleConflit ??= $detail;
            }
        }

        if ($groupeConflit) {
            return 'Ce groupe a déjà un cours sur cet horaire : '.$groupeConflit.'. Deux cours ne peuvent pas se superposer.';
        }
        if ($profConflit) {
            return 'Cet enseignant a déjà un cours sur cet horaire : '.$profConflit.'. Deux cours ne peuvent pas se superposer.';
        }
        if ($salleConflit) {
            return 'Cette salle est déjà occupée sur cet horaire : '.$salleConflit.'. Deux cours ne peuvent pas se superposer.';
        }

        return null;
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

    /**
     * @return array{cours: string, absence: string, retard: string, groupes: string}
     */
    private function heuresParent(): array
    {
        $maintenant = now();
        $seances = EcoleSeance::query()->get();
        $cours = 0;
        $minutesParJour = [];

        foreach ($seances as $seance) {
            if (! $seance->date) {
                continue;
            }

            $minutes = max(0, EcoleTimetable::minutes($seance->heure('heure_fin')) - EcoleTimetable::minutes($seance->heure('heure_debut')));
            $jour = $seance->ecole_groupe_id.'|'.$seance->date->toDateString();
            $minutesParJour[$jour] = ($minutesParJour[$jour] ?? 0) + $minutes;

            $fin = $seance->date->copy()->setTimeFromTimeString($seance->heure('heure_fin'));
            if ($fin->lte($maintenant)) {
                $cours += $minutes;
            }
        }

        $pointages = EcoleAssiduite::query()->get(['ecole_etudiant_id', 'date', 'statut']);
        $groupesEleves = DB::table('ecole_etudiants')
            ->whereIn('id', $pointages->pluck('ecole_etudiant_id')->filter()->unique()->all())
            ->pluck('ecole_groupe_id', 'id');
        $absence = 0;
        $retard = 0;

        foreach ($pointages as $pointage) {
            $groupeId = $groupesEleves[$pointage->ecole_etudiant_id] ?? null;
            if (! $groupeId || ! $pointage->date) {
                continue;
            }

            $minutes = $minutesParJour[$groupeId.'|'.$pointage->date->toDateString()] ?? 0;
            if ($pointage->statut === 'absent') {
                $absence += $minutes;
            } elseif ($pointage->statut === 'retard') {
                $retard += $minutes;
            }
        }

        $noms = EcoleGroupe::query()->orderBy('nom')->pluck('nom')->filter()->implode(', ');

        return [
            'cours' => $this->duree($cours),
            'absence' => $this->duree($absence),
            'retard' => $this->duree($retard),
            'groupes' => $noms !== '' ? $noms : 'Aucun groupe',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EcoleEtudiant>  $etudiants
     * @return array{cours: string, absence: string, retard: string, groupes: string}
     */
    private function heuresDesEtudiants($etudiants): array
    {
        $maintenant = now();
        $groupeIds = $etudiants->pluck('ecole_groupe_id')->filter()->unique()->values();
        $eleveIds = $etudiants->pluck('id')->filter()->values();
        if ($groupeIds->isEmpty()) {
            return [
                'cours' => $this->duree(0),
                'absence' => $this->duree(0),
                'retard' => $this->duree(0),
                'groupes' => 'Aucun groupe',
            ];
        }
        $seances = EcoleSeance::query()->whereIn('ecole_groupe_id', $groupeIds->all())->get();
        $cours = 0;
        $minutesParJour = [];

        foreach ($seances as $seance) {
            if (! $seance->date) {
                continue;
            }
            $minutes = max(0, EcoleTimetable::minutes($seance->heure('heure_fin')) - EcoleTimetable::minutes($seance->heure('heure_debut')));
            $cle = $seance->ecole_groupe_id.'|'.$seance->date->toDateString();
            $minutesParJour[$cle] = ($minutesParJour[$cle] ?? 0) + $minutes;
            $fin = $seance->date->copy()->setTimeFromTimeString($seance->heure('heure_fin'));
            if ($fin->lte($maintenant)) {
                $cours += $minutes;
            }
        }

        $pointages = EcoleAssiduite::query()
            ->whereIn('ecole_etudiant_id', $eleveIds->all())
            ->get(['ecole_etudiant_id', 'date', 'statut']);
        $absence = 0;
        $retard = 0;
        $groupesEleves = $etudiants->pluck('ecole_groupe_id', 'id');
        foreach ($pointages as $pointage) {
            $groupeId = $groupesEleves[$pointage->ecole_etudiant_id] ?? null;
            if (! $groupeId || ! $pointage->date) {
                continue;
            }
            $minutes = $minutesParJour[$groupeId.'|'.$pointage->date->toDateString()] ?? 0;
            if ($pointage->statut === 'absent') {
                $absence += $minutes;
            } elseif ($pointage->statut === 'retard') {
                $retard += $minutes;
            }
        }

        $noms = EcoleGroupe::query()->whereIn('id', $groupeIds->all())->orderBy('nom')->pluck('nom')->implode(', ');

        return [
            'cours' => $this->duree($cours),
            'absence' => $this->duree($absence),
            'retard' => $this->duree($retard),
            'groupes' => $noms !== '' ? $noms : 'Aucun groupe',
        ];
    }

    private function duree(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 h';
        }

        $heures = intdiv($minutes, 60);
        $reste = $minutes % 60;
        if ($heures === 0) {
            return $reste.' min';
        }
        if ($reste === 0) {
            return $heures.' h';
        }

        return $heures.' h '.$reste.' min';
    }

    private function refuserHorsEquipe(Request $request): void
    {
        if (! $request->user()->peutModifierFicheEleve()) {
            abort(403, 'Seule l’équipe pédagogique peut modifier cette fiche.');
        }
    }

    private function vide(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function compteParent(Request $request, array $data, bool &$cree): User
    {
        $departementId = $request->user()->currentDepartementId();
        if (! $departementId) {
            throw ValidationException::withMessages([
                'parent_email' => 'Choisissez le département pédagogique avant de créer un compte parent.',
            ]);
        }

        $email = mb_strtolower(trim($data['parent_email']));
        $existant = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existant && (! $existant->isParent() || (int) $existant->departement_id !== (int) $departementId)) {
            throw ValidationException::withMessages([
                'parent_email' => 'Cet email est déjà utilisé par un autre compte.',
            ]);
        }

        if ($existant) {
            $existant->forceFill([
                'name' => trim($data['parent_nom']),
                'password' => $data['parent_password'],
            ])->save();
            $user = $existant;
            $cree = false;
        } else {
            $user = User::create([
                'name' => trim($data['parent_nom']),
                'email' => $email,
                'password' => $data['parent_password'],
                'role' => 'parent',
                'departement_id' => $departementId,
            ]);
            $cree = true;
        }

        $permissionId = Permission::query()->where('key', 'ecole.view')->value('id');
        if (! $permissionId) {
            Permission::syncCatalog();
            $permissionId = Permission::query()->where('key', 'ecole.view')->value('id');
        }
        if ($permissionId) {
            $user->permissions()->syncWithoutDetaching([$permissionId]);
        }

        return $user;
    }

    private function compteEnseignantUtilisateur(Request $request, array $data, bool &$cree): User
    {
        $departementId = $request->user()->currentDepartementId();
        if (! $departementId) {
            throw ValidationException::withMessages([
                'enseignant_email' => 'Choisissez le département pédagogique avant de créer un compte enseignant.',
            ]);
        }

        $email = mb_strtolower(trim($data['enseignant_email']));
        $existant = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existant && (! $existant->isEnseignant() || (int) $existant->departement_id !== (int) $departementId)) {
            throw ValidationException::withMessages([
                'enseignant_email' => 'Cet email est déjà utilisé par un autre compte.',
            ]);
        }

        $nom = trim($data['prenom'].' '.$data['nom']);
        if ($existant) {
            $existant->forceFill([
                'name' => $nom,
                'password' => $data['enseignant_password'],
            ])->save();
            $user = $existant;
            $cree = false;
        } else {
            $user = User::create([
                'name' => $nom,
                'email' => $email,
                'password' => $data['enseignant_password'],
                'role' => 'enseignant',
                'departement_id' => $departementId,
            ]);
            $cree = true;
        }

        $permissionId = Permission::query()->where('key', 'ecole.view')->value('id');
        if (! $permissionId) {
            Permission::syncCatalog();
            $permissionId = Permission::query()->where('key', 'ecole.view')->value('id');
        }
        if ($permissionId) {
            $user->permissions()->sync([$permissionId]);
        }

        return $user;
    }
}
