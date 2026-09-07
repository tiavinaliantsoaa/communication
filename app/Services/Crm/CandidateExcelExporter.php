<?php

namespace App\Services\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmIntake;
use App\Models\CrmProgramme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CandidateExcelExporter
{
    public const POPULATIONS = [
        'actifs' => 'Candidats en cours',
        'abandons' => 'Candidats en abandon',
        'tous' => 'Tous les candidats',
    ];

    public const COLUMNS = [
        'full_name' => 'Nom complet',
        'prenom' => 'Prénom',
        'nom' => 'Nom',
        'telephone' => 'Téléphone',
        'email' => 'E-mail',
        'programme' => 'Programme',
        'annee_academique' => 'Année / Intake',
        'statut' => 'Statut',
        'advisor' => 'Conseiller',
        'genre' => 'Genre',
        'date_naissance' => 'Date de naissance',
        'adresse' => 'Adresse',
        'contact_parent_1' => 'Contact parent 1',
        'contact_parent_2' => 'Contact parent 2',
        'niveau_etudes' => 'Niveau d’études',
        'etablissement_origine' => 'Établissement',
        'source' => 'Source',
        'notes' => 'Notes',
        'abandon' => 'Abandon',
        'abandon_raison' => 'Motif d’abandon',
        'last_interaction_at' => 'Dernière interaction',
    ];

    public const DEFAULT_COLUMNS = [
        'full_name',
        'telephone',
    ];

    public const COLUMN_GROUPS = [
        'Identité et contact' => ['full_name', 'prenom', 'nom', 'telephone', 'email', 'genre', 'date_naissance', 'adresse', 'contact_parent_1', 'contact_parent_2'],
        'Scolarité' => ['programme', 'annee_academique', 'niveau_etudes', 'etablissement_origine'],
        'CRM' => ['statut', 'advisor', 'source', 'notes', 'abandon', 'abandon_raison', 'last_interaction_at'],
    ];

    public function __construct(
        private SimpleXlsxWriter $xlsx
    ) {}

    /**
     * @return array{population: string, programmes: list<string>, intakes: list<string>, statut: string, advisor_id: string, columns: list<string>}
     */
    public function filtersFromRequest(Request $request): array
    {
        $population = (string) $request->input('population', 'actifs');
        if (! array_key_exists($population, self::POPULATIONS)) {
            $population = 'actifs';
        }

        $columns = $this->stringList($request->input('columns', self::DEFAULT_COLUMNS));
        $allowed = array_keys(self::COLUMNS);
        $columns = array_values(array_intersect($columns, $allowed));
        if ($columns === []) {
            $columns = self::DEFAULT_COLUMNS;
        }

        return [
            'population' => $population,
            'programmes' => $this->stringList($request->input('programme', [])),
            'intakes' => $this->stringList($request->input('intake', [])),
            'statut' => trim((string) $request->input('statut', '')),
            'advisor_id' => trim((string) $request->input('advisor_id', '')),
            'columns' => $columns,
        ];
    }

    /**
     * @param  array{population: string, programmes: list<string>, intakes: list<string>, statut: string, advisor_id: string}  $filters
     */
    public function query(array $filters): Builder
    {
        return CrmCandidate::query()
            ->with('advisor')
            ->when($filters['population'] === 'actifs', fn (Builder $q) => $q->where(function (Builder $inner) {
                $inner->where('abandon', false)->orWhereNull('abandon');
            }))
            ->when($filters['population'] === 'abandons', fn (Builder $q) => $q->where('abandon', true))
            ->when($filters['programmes'] !== [], fn (Builder $q) => $q->whereIn('programme', $filters['programmes']))
            ->when($filters['intakes'] !== [], fn (Builder $q) => $q->whereIn('annee_academique', $filters['intakes']))
            ->when($filters['statut'] !== '' && array_key_exists($filters['statut'], CrmCandidate::STATUTS), fn (Builder $q) => $q->where('statut', $filters['statut']))
            ->when($filters['advisor_id'] !== '', fn (Builder $q) => $q->where('advisor_id', $filters['advisor_id']))
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    /**
     * @param  array{columns: list<string>}  $filters
     * @param  iterable<int, CrmCandidate>  $candidates
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function table(iterable $candidates, array $filters): array
    {
        $columns = $filters['columns'];
        $headers = array_map(fn (string $key) => self::COLUMNS[$key], $columns);
        $rows = [];

        foreach ($candidates as $candidate) {
            $row = [];
            foreach ($columns as $key) {
                $row[] = $this->cellValue($candidate, $key);
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param  array{columns: list<string>}  $filters
     */
    public function xlsx(iterable $candidates, array $filters): string
    {
        $table = $this->table($candidates, $filters);

        return $this->xlsx->build($table['headers'], $table['rows']);
    }

    /**
     * @return list<string>
     */
    public function programmeOptions(): array
    {
        return $this->mergeLabels(
            CrmProgramme::optionsForSelect(),
            CrmCandidate::query()
                ->whereNotNull('programme')
                ->where('programme', '!=', '')
                ->distinct()
                ->orderBy('programme')
                ->pluck('programme')
                ->all()
        );
    }

    /**
     * @return list<string>
     */
    public function intakeOptions(): array
    {
        return $this->mergeLabels(
            CrmIntake::optionsForSelect(),
            CrmCandidate::query()
                ->whereNotNull('annee_academique')
                ->where('annee_academique', '!=', '')
                ->distinct()
                ->orderBy('annee_academique')
                ->pluck('annee_academique')
                ->all()
        );
    }

    private function cellValue(CrmCandidate $candidate, string $key): string
    {
        return match ($key) {
            'full_name' => $candidate->full_name,
            'prenom' => (string) $candidate->prenom,
            'nom' => (string) $candidate->nom,
            'telephone' => (string) ($candidate->telephone ?: ''),
            'email' => (string) ($candidate->email ?: ''),
            'programme' => (string) ($candidate->programme ?: ''),
            'annee_academique' => (string) ($candidate->annee_academique ?: ''),
            'statut' => $candidate->statut_label,
            'advisor' => (string) ($candidate->advisor?->name ?: ''),
            'genre' => $candidate->genre_label === '—' ? '' : $candidate->genre_label,
            'date_naissance' => $candidate->date_naissance ? $candidate->date_naissance->format('d/m/Y') : '',
            'adresse' => (string) ($candidate->adresse ?: ''),
            'contact_parent_1' => (string) ($candidate->contact_parent_1 ?: ''),
            'contact_parent_2' => (string) ($candidate->contact_parent_2 ?: ''),
            'niveau_etudes' => (string) ($candidate->niveau_etudes ?: ''),
            'etablissement_origine' => (string) ($candidate->etablissement_origine ?: ''),
            'source' => $candidate->source_label === '—' ? '' : $candidate->source_label,
            'notes' => (string) ($candidate->notes ?: ''),
            'abandon' => $candidate->abandon ? 'Oui' : 'Non',
            'abandon_raison' => (string) ($candidate->abandon_raison ?: ''),
            'last_interaction_at' => $candidate->last_interaction_at ? $candidate->last_interaction_at->format('d/m/Y H:i') : '',
            default => '',
        };
    }

    /**
     * @param  list<string>  $preferred
     * @param  list<string>  $extra
     * @return list<string>
     */
    private function mergeLabels(array $preferred, array $extra): array
    {
        $labels = $preferred;
        foreach ($extra as $label) {
            if (! in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        return array_values($labels);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
