<?php

namespace App\Services\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\CrmIntake;
use App\Models\CrmProgramme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GlideImporter
{
    public function __construct(private readonly GlideFieldMapper $mapper) {}

    /**
     * @param  array{dry_run?: bool, skip_abandoned?: bool, update_existing?: bool, create_lookups?: bool, academic_year?: string|null}  $options
     * @return array<string, mixed>
     */
    public function import(?string $candidatesPath, ?string $documentsPath, array $options, User $user): array
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $dryRun = (bool) ($options['dry_run'] ?? false);
        $skipAbandoned = (bool) ($options['skip_abandoned'] ?? false);
        $updateExisting = (bool) ($options['update_existing'] ?? false);
        $createLookups = (bool) ($options['create_lookups'] ?? true);
        $academicYear = $options['academic_year'] ?? null;
        if ($academicYear === 'all' || $academicYear === '') {
            $academicYear = null;
        }

        $report = $this->emptyReport($dryRun);

        $candidateRows = $candidatesPath ? GlideCsv::rows($candidatesPath) : [];
        $documentRows = $documentsPath ? GlideCsv::rows($documentsPath) : [];

        if ($candidatesPath && $candidateRows !== [] && $this->looksLikeDocuments($candidateRows[0])) {
            $report['errors'][] = 'Le fichier candidats ressemble à un export de documents Glide. Inversez les fichiers.';

            return $report;
        }
        if ($documentsPath && $documentRows !== [] && $this->looksLikeCandidates($documentRows[0])) {
            $report['errors'][] = 'Le fichier documents ressemble à un export de candidats Glide. Inversez les fichiers.';

            return $report;
        }

        $run = function () use (
            $candidateRows,
            $documentRows,
            $dryRun,
            $skipAbandoned,
            $updateExisting,
            $createLookups,
            $academicYear,
            $user,
            &$report
        ) {
            $advisorIdsByName = $this->advisorIndex();
            $existingByGlide = $this->candidateIdMap();
            $idMap = $candidateRows === [] ? $this->candidateIdMap($academicYear) : [];

            $pipelineOrders = CrmCandidate::query()
                ->select('statut')
                ->selectRaw('MAX(pipeline_order) as m')
                ->groupBy('statut')
                ->pluck('m', 'statut')
                ->map(fn ($v) => (int) $v)
                ->all();

            $programmesToEnsure = [];
            $intakesToEnsure = [];

            foreach ($candidateRows as $row) {
                $mapped = $this->mapper->mapCandidate($row, $advisorIdsByName);
                if ($mapped === null) {
                    $report['candidates_skipped_invalid']++;
                    $this->pushError($report, 'Candidat ignoré : identifiant ou nom manquant.');

                    continue;
                }

                if (! $this->mapper->matchesYearFilter($row, $academicYear)) {
                    $report['candidates_skipped_year']++;

                    continue;
                }

                if ($skipAbandoned && ($mapped['abandon'] ?? false)) {
                    $report['candidates_skipped_abandon']++;

                    continue;
                }

                if (($mapped['programme'] ?? null) && $createLookups) {
                    $programmesToEnsure[$mapped['programme']] = true;
                }
                if (($mapped['annee_academique'] ?? null) && $createLookups) {
                    $intakesToEnsure[$mapped['annee_academique']] = true;
                }

                $glideId = $mapped['glide_applicant_id'];
                $existingId = $existingByGlide[$glideId] ?? $idMap[$glideId] ?? null;
                if (! $existingId && ! empty($mapped['glide_row_id'])) {
                    $existingId = $existingByGlide[$mapped['glide_row_id']] ?? null;
                }

                if ($existingId && ! $updateExisting) {
                    $report['candidates_skipped_existing']++;
                    $idMap[$glideId] = (int) $existingId;
                    if (! empty($mapped['glide_row_id'])) {
                        $idMap[$mapped['glide_row_id']] = (int) $existingId;
                    }

                    continue;
                }

                $attrs = $mapped;
                $createdAt = $attrs['created_at'] ?? null;
                unset($attrs['created_at']);

                $attrs['statut'] = CrmCandidate::resolveStatutFromAttributes($attrs);
                $attrs['created_by'] = $user->id;
                $attrs['last_interaction_at'] = $createdAt ?? now();

                if ($dryRun) {
                    if ($existingId) {
                        $report['candidates_updated']++;
                        $idMap[$glideId] = (int) $existingId;
                    } else {
                        $report['candidates_created']++;
                        $idMap[$glideId] = -1;
                    }
                    if (! empty($mapped['glide_row_id'])) {
                        $idMap[$mapped['glide_row_id']] = $idMap[$glideId];
                    }

                    continue;
                }

                if ($existingId) {
                    $candidate = CrmCandidate::query()->find($existingId);
                    if (! $candidate) {
                        $report['candidates_skipped_invalid']++;

                        continue;
                    }
                    if (filled($candidate->notes)) {
                        unset($attrs['notes']);
                    }
                    unset($attrs['created_by']);
                    $candidate->forceFill($attrs)->save();
                    $idMap[$glideId] = $candidate->id;
                    if (! empty($mapped['glide_row_id'])) {
                        $idMap[$mapped['glide_row_id']] = $candidate->id;
                    }
                    $report['candidates_updated']++;
                } else {
                    $pipelineOrders[$attrs['statut']] = ($pipelineOrders[$attrs['statut']] ?? 0) + 1;
                    $attrs['pipeline_order'] = $pipelineOrders[$attrs['statut']];
                    $candidate = new CrmCandidate;
                    $candidate->forceFill($attrs);
                    if ($createdAt) {
                        $candidate->created_at = $createdAt;
                        $candidate->updated_at = $createdAt;
                    }
                    $candidate->save();
                    $existingByGlide[$glideId] = $candidate->id;
                    $idMap[$glideId] = $candidate->id;
                    if (! empty($mapped['glide_row_id'])) {
                        $idMap[$mapped['glide_row_id']] = $candidate->id;
                    }
                    $report['candidates_created']++;
                }
            }

            if ($createLookups) {
                $report['programmes_created'] = $this->ensureLabels(CrmProgramme::class, array_keys($programmesToEnsure), $dryRun);
                $report['intakes_created'] = $this->ensureLabels(CrmIntake::class, array_keys($intakesToEnsure), $dryRun);
            }

            $this->importDocuments($documentRows, $dryRun, $createLookups, $user, $idMap, $report);
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        return $report;
    }

    /**
     * @param  list<array<string, string>>  $documentRows
     * @param  array<string, int>  $idMap
     * @param  array<string, mixed>  $report
     */
    private function importDocuments(array $documentRows, bool $dryRun, bool $createLookups, User $user, array $idMap, array &$report): void
    {
        if ($documentRows === []) {
            return;
        }
        $existingDocs = CrmCandidateDocument::query()
            ->whereNotNull('glide_document_id')
            ->pluck('id', 'glide_document_id')
            ->all();

        $typeIds = [];
        foreach (CrmDocumentType::query()->get(['id', 'label']) as $type) {
            $typeIds[mb_strtolower($type->label)] = $type->id;
        }
        $maxTypePosition = (int) CrmDocumentType::max('position');
        $typesToCreate = [];

        foreach ($documentRows as $row) {
            $mapped = $this->mapper->mapDocument($row);
            if ($mapped === null) {
                $report['documents_skipped_invalid']++;

                continue;
            }

            $candidateId = $idMap[$mapped['glide_applicant_id']] ?? null;
            if (! $candidateId) {
                $report['documents_skipped_unmatched']++;

                continue;
            }

            $typeKey = mb_strtolower($mapped['type_label']);
            if (! isset($typeIds[$typeKey])) {
                if (! $createLookups) {
                    $report['documents_skipped_invalid']++;

                    continue;
                }
                $typesToCreate[$mapped['type_label']] = true;
            }

            $glideDocId = $mapped['glide_document_id'];
            $already = $glideDocId ? ($existingDocs[$glideDocId] ?? null) : null;

            if ($dryRun) {
                if ($already) {
                    $report['documents_updated']++;
                } else {
                    $report['documents_created']++;
                }

                continue;
            }

            if (! isset($typeIds[$typeKey])) {
                $maxTypePosition++;
                $created = CrmDocumentType::create([
                    'label' => $mapped['type_label'],
                    'is_required' => false,
                    'actif' => true,
                    'position' => $maxTypePosition,
                ]);
                $typeIds[$typeKey] = $created->id;
                $report['document_types_created']++;
            }

            $payload = [
                'crm_candidate_id' => $candidateId,
                'crm_document_type_id' => $typeIds[$typeKey],
                'path' => '',
                'external_url' => $mapped['external_url'],
                'original_name' => $mapped['original_name'],
                'mime' => $mapped['mime'],
                'size' => null,
                'uploaded_by' => $user->id,
                'glide_document_id' => $glideDocId,
            ];

            if ($already) {
                CrmCandidateDocument::query()->whereKey($already)->update($payload);
                $report['documents_updated']++;
            } else {
                $doc = CrmCandidateDocument::create($payload);
                if ($glideDocId) {
                    $existingDocs[$glideDocId] = $doc->id;
                }
                $report['documents_created']++;
            }
        }

        if ($dryRun && $createLookups) {
            foreach (array_keys($typesToCreate) as $label) {
                if (! isset($typeIds[mb_strtolower($label)])) {
                    $report['document_types_created']++;
                }
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function candidateIdMap(?string $academicYear = null): array
    {
        $map = [];
        $query = CrmCandidate::query()
            ->where(function ($q) {
                $q->whereNotNull('glide_applicant_id')->orWhereNotNull('glide_row_id');
            });

        if ($academicYear === '2025-2026') {
            $query->where(function ($q) {
                $q->where('annee_academique', 'like', '%2025%')
                    ->orWhere('annee_academique', 'like', '%2026%')
                    ->orWhere('programme', 'like', '%2025%')
                    ->orWhere('programme', 'like', '%2026%');
            });
        }

        $query->get(['id', 'glide_applicant_id', 'glide_row_id'])
            ->each(function (CrmCandidate $candidate) use (&$map) {
                if ($candidate->glide_applicant_id) {
                    $map[$candidate->glide_applicant_id] = $candidate->id;
                }
                if ($candidate->glide_row_id) {
                    $map[$candidate->glide_row_id] = $candidate->id;
                }
            });

        return $map;
    }

    /**
     * @return array{candidates: int, documents: int}
     */
    public function purgeAll(): array
    {
        return DB::transaction(function () {
            $documents = CrmCandidateDocument::query()->count();
            $candidates = CrmCandidate::query()->count();

            foreach (CrmCandidateDocument::query()->where('path', '!=', '')->cursor() as $doc) {
                if (filled($doc->path)) {
                    Storage::disk('public')->delete($doc->path);
                }
            }

            Storage::disk('public')->deleteDirectory('crm/documents');

            CrmCandidate::query()->delete();

            return [
                'candidates' => $candidates,
                'documents' => $documents,
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    private function advisorIndex(): array
    {
        $index = [];
        foreach (User::query()->get(['id', 'name', 'email']) as $user) {
            $index[$this->mapper->normalizePersonName($user->name)] = $user->id;
            $local = strstr((string) $user->email, '@', true);
            if (is_string($local) && $local !== '') {
                $index[$this->mapper->normalizePersonName(str_replace(['.', '_', '-'], ' ', $local))] = $user->id;
            }
        }

        return $index;
    }

    /**
     * @param  class-string  $model
     * @param  list<string>  $labels
     */
    private function ensureLabels(string $model, array $labels, bool $dryRun): int
    {
        $created = 0;
        $existing = $model::query()->pluck('id', 'label')->all();
        $existingLower = [];
        foreach (array_keys($existing) as $label) {
            $existingLower[mb_strtolower($label)] = true;
        }
        $max = (int) $model::max('position');

        foreach ($labels as $label) {
            if ($label === '' || isset($existingLower[mb_strtolower($label)])) {
                continue;
            }
            $created++;
            if ($dryRun) {
                continue;
            }
            $max++;
            $model::create([
                'label' => $label,
                'actif' => true,
                'position' => $max,
            ]);
            $existingLower[mb_strtolower($label)] = true;
        }

        return $created;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function looksLikeDocuments(array $row): bool
    {
        return array_key_exists('applicants unique/id', $row) && (array_key_exists('url', $row) || array_key_exists('type', $row));
    }

    /**
     * @param  array<string, string>  $row
     */
    private function looksLikeCandidates(array $row): bool
    {
        return array_key_exists('unique applicants id', $row) || array_key_exists('info/first name', $row);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,
            'candidates_created' => 0,
            'candidates_updated' => 0,
            'candidates_skipped_existing' => 0,
            'candidates_skipped_invalid' => 0,
            'candidates_skipped_abandon' => 0,
            'candidates_skipped_year' => 0,
            'documents_created' => 0,
            'documents_updated' => 0,
            'documents_skipped_unmatched' => 0,
            'documents_skipped_invalid' => 0,
            'programmes_created' => 0,
            'intakes_created' => 0,
            'document_types_created' => 0,
            'errors' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function pushError(array &$report, string $message): void
    {
        if (count($report['errors']) >= 15) {
            return;
        }
        $report['errors'][] = $message;
    }
}
