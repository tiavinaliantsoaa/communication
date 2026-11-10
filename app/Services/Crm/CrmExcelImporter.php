<?php

namespace App\Services\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\CrmIntake;
use App\Models\CrmProgramme;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrmExcelImporter
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private readonly SimpleXlsxReader $reader,
        private readonly GlideFieldMapper $mapper,
    ) {}

    /**
     * @param  array{
     *     dry_run?: bool,
     *     update_existing?: bool,
     *     download?: bool,
     *     download_limit?: int|null,
     *     profile_files?: bool,
     *     create_lookups?: bool,
     *     progress?: callable|null
     * }  $options
     * @return array<string, mixed>
     */
    public function import(?string $candidatesPath, ?string $documentsPath, array $options, User $user): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $dryRun = (bool) ($options['dry_run'] ?? false);
        $updateExisting = (bool) ($options['update_existing'] ?? false);
        $download = (bool) ($options['download'] ?? true);
        $profileFiles = (bool) ($options['profile_files'] ?? false);
        $createLookups = (bool) ($options['create_lookups'] ?? true);
        $downloadLimit = isset($options['download_limit']) ? max(0, (int) $options['download_limit']) : null;
        $progress = $options['progress'] ?? null;

        $report = $this->emptyReport($dryRun);
        $candidateRows = $candidatesPath ? $this->reader->rows($candidatesPath) : [];
        $documentRows = $documentsPath ? $this->reader->rows($documentsPath) : [];

        if ($candidateRows !== [] && $this->looksLikeDocuments($candidateRows[0])) {
            $report['errors'][] = 'Le fichier candidats ressemble à un export de documents. Inversez les fichiers.';

            return $report;
        }
        if ($documentRows !== [] && $this->looksLikeCandidates($documentRows[0])) {
            $report['errors'][] = 'Le fichier documents ressemble à un export de candidats. Inversez les fichiers.';

            return $report;
        }

        $profileLinks = [];
        $advisorIds = $this->advisorIndex();
        $existingByGlide = $this->candidateIdMap();
        $idMap = $existingByGlide;
        $programmes = [];
        $intakes = [];

        $pipelineOrders = CrmCandidate::query()
            ->select('statut')
            ->selectRaw('MAX(pipeline_order) as m')
            ->groupBy('statut')
            ->pluck('m', 'statut')
            ->map(fn ($value) => (int) $value)
            ->all();

        $saveCandidates = function () use (
            $candidateRows,
            $dryRun,
            $updateExisting,
            $profileFiles,
            $user,
            $advisorIds,
            &$existingByGlide,
            &$idMap,
            &$programmes,
            &$intakes,
            &$pipelineOrders,
            &$profileLinks,
            &$report,
            $progress
        ) {
            $total = count($candidateRows);
            foreach ($candidateRows as $index => $row) {
                if ($progress) {
                    $progress('candidates', $index + 1, $total);
                }

                $row = $this->prepareCandidateRow($row);
                $mapped = $this->mapper->mapCandidate($row, $advisorIds);
                if ($mapped === null) {
                    $report['candidates_skipped_invalid']++;

                    continue;
                }

                $this->applyWorkbookFields($mapped, $row);
                if ($profileFiles) {
                    array_push($profileLinks, ...$this->profileLinks($row, $mapped['glide_applicant_id']));
                }

                if (! empty($mapped['programme'])) {
                    $programmes[$mapped['programme']] = true;
                }
                if (! empty($mapped['annee_academique'])) {
                    $intakes[$mapped['annee_academique']] = true;
                }

                $glideId = $mapped['glide_applicant_id'];
                $existingId = $existingByGlide[$glideId] ?? null;
                if (! $existingId && ! empty($mapped['glide_row_id'])) {
                    $existingId = $existingByGlide[$mapped['glide_row_id']] ?? null;
                }

                $this->rememberCandidate($idMap, $mapped, $existingId ? (int) $existingId : -1);

                if ($existingId && ! $updateExisting) {
                    $report['candidates_skipped_existing']++;

                    continue;
                }

                $attrs = $mapped;
                $createdAt = $attrs['created_at'] ?? null;
                unset($attrs['created_at']);
                $attrs['statut'] = CrmCandidate::resolveStatutFromAttributes($attrs);
                $attrs['last_interaction_at'] = $createdAt ?? now();

                if ($dryRun) {
                    if ($existingId) {
                        $report['candidates_updated']++;
                    } else {
                        $report['candidates_created']++;
                        $this->rememberCandidate($existingByGlide, $mapped, -1);
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
                    unset(
                        $attrs['created_by'],
                        $attrs['paiement_frais_test'],
                        $attrs['validation_test'],
                        $attrs['lettre_admission'],
                        $attrs['paiement_acompte'],
                        $attrs['paiement_totalite'],
                        $attrs['statut']
                    );
                    if (! ($attrs['abandon'] ?? false)) {
                        unset($attrs['abandon'], $attrs['abandon_raison']);
                    }
                    $candidate->forceFill($attrs)->save();
                    $this->rememberCandidate($idMap, $mapped, $candidate->id);
                    $this->rememberCandidate($existingByGlide, $mapped, $candidate->id);
                    $report['candidates_updated']++;

                    continue;
                }

                $pipelineOrders[$attrs['statut']] = ($pipelineOrders[$attrs['statut']] ?? 0) + 1;
                $attrs['pipeline_order'] = $pipelineOrders[$attrs['statut']];
                $attrs['created_by'] = $user->id;
                $candidate = new CrmCandidate;
                $candidate->forceFill($attrs);
                if ($createdAt) {
                    $candidate->created_at = $createdAt;
                    $candidate->updated_at = $createdAt;
                }
                $candidate->save();
                $this->rememberCandidate($idMap, $mapped, $candidate->id);
                $this->rememberCandidate($existingByGlide, $mapped, $candidate->id);
                $report['candidates_created']++;
            }
        };

        if ($dryRun || $candidateRows === []) {
            $saveCandidates();
        } else {
            DB::transaction($saveCandidates);
        }

        if ($createLookups) {
            $report['programmes_created'] = $this->ensureLabels(CrmProgramme::class, array_keys($programmes), $dryRun);
            $report['intakes_created'] = $this->ensureLabels(CrmIntake::class, array_keys($intakes), $dryRun);
        }

        $links = $this->documentLinks($documentRows);
        if ($profileLinks !== []) {
            $knownUrls = [];
            foreach ($links as $link) {
                if (empty($link['external_url'])) {
                    continue;
                }
                $knownUrls[$link['external_url']] = true;
            }
            foreach ($profileLinks as $link) {
                if (isset($knownUrls[$link['external_url']])) {
                    continue;
                }
                $knownUrls[$link['external_url']] = true;
                $links[] = $link;
            }
        }

        $this->importLinks($links, $dryRun, $download, $downloadLimit, $createLookups, $user, $idMap, $report, $progress);

        return $report;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function prepareCandidateRow(array $row): array
    {
        foreach ([
            'info/ date of birth',
            'created_at',
            'immatriculation/ administratif start date',
            'immatriculation/ postponed start date',
        ] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $converted = $this->excelDateToString($row[$key]);
            if ($converted !== null) {
                $row[$key] = $converted;
            } elseif ($this->isOpaqueId($row[$key])) {
                $row[$key] = '';
            }
        }

        foreach ([
            'info/first name',
            'info/last name',
            'info/ place of birth',
            'current situation/ level',
            'wish/ filiere',
            'wish/ figs schools',
            'wish/ town france',
            'localisation/province',
            'localisation/ address',
            'localisation/ region',
            'localisation/ city',
        ] as $key) {
            if (isset($row[$key]) && $this->isOpaqueId($row[$key])) {
                $row[$key] = '';
            }
        }

        $civility = mb_strtolower(trim($row['info/civility'] ?? ''));
        $accepted = ['homme', 'monsieur', 'm', 'mr', 'male', 'h', 'femme', 'madame', 'mademoiselle', 'mme', 'mlle', 'female', 'f', 'autre'];
        if ($civility !== '' && ! in_array($civility, $accepted, true)) {
            $row['info/civility'] = '';
        }

        $creator = trim($row['crée par'] ?? '');
        $responsible = trim($row['responsible/id'] ?? '');
        $advisorName = $this->looksLikePersonName($creator)
            ? $creator
            : ($this->looksLikePersonName($responsible) ? $responsible : '');
        if ($advisorName !== '') {
            $row['responsible/ name'] = $advisorName;
        }

        $source = trim($row['sources/ id'] ?? '');
        if ($source !== '' && ! $this->isOpaqueId($source) && preg_match('/\p{L}/u', $source)) {
            $row['sources/ name'] = $source;
        }

        $reason = trim($row['abandon/ reason id'] ?? '');
        if ($reason !== '' && ! $this->isOpaqueId($reason) && preg_match('/\p{L}/u', $reason)) {
            $row['abandon/ reason display'] = $reason;
            $row['abandon/ is abandon'] = 'true';
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $mapped
     * @param  array<string, string>  $row
     */
    private function applyWorkbookFields(array &$mapped, array $row): void
    {
        $facebook = trim($row['info/page facebook'] ?? '');
        if ($facebook !== '' && filter_var($facebook, FILTER_VALIDATE_URL) && empty($mapped['source'])) {
            $mapped['source'] = 'facebook';
            $mapped['facebook_profil_url'] = $this->limit($facebook, 500);
        }

        $escmEmail = trim($row['immatriculation/ email escm'] ?? '');
        if (filter_var($escmEmail, FILTER_VALIDATE_EMAIL)) {
            if (empty($mapped['email'])) {
                $mapped['email'] = $this->limit($escmEmail, 255);
            }
        } else {
            $escmEmail = '';
        }

        $lines = [];
        $this->pushNote($lines, 'Année de paiement', $this->readable($row['année de paiement'] ?? ''));
        if ($escmEmail !== '' && $escmEmail !== ($mapped['email'] ?? null)) {
            $this->pushNote($lines, 'E-mail ESCM', $escmEmail);
        }
        $this->pushNote($lines, 'Bac préparé', $this->readable($row['current situation/ prepared bacc'] ?? ''));
        $this->pushNote($lines, 'Année du bac', $this->readable($row['current situation/ year bacc'] ?? ''));
        $this->pushNote($lines, 'Autre diplôme', $this->readable($row['current situation/ other diploma name'] ?? ''));
        $this->pushNote($lines, 'École visée', $this->readable($row['wish/ figs schools'] ?? ''));
        $this->pushNote($lines, 'Ville visée en France', $this->readable($row['wish/ town france'] ?? ''));
        if ($this->mapper->isTruthy($row['details/have family in france'] ?? '')) {
            $lines[] = 'Famille en France : oui';
        }
        if ($this->mapper->isTruthy($row['info/prospect chaud'] ?? '')) {
            $lines[] = 'Prospect chaud : oui';
        }
        if ($this->mapper->isTruthy($row['applicant finance by himself'] ?? '')) {
            $lines[] = 'Financement personnel : oui';
        }

        if ($lines === []) {
            return;
        }

        $notes = trim((string) ($mapped['notes'] ?? ''));
        $extra = implode("\n", $lines);
        $mapped['notes'] = $this->limit($notes === '' ? $extra : $notes."\n".$extra, 5000);
    }

    /**
     * @param  list<string>  $lines
     */
    private function pushNote(array &$lines, string $label, string $value): void
    {
        if ($value !== '') {
            $lines[] = $label.' : '.$value;
        }
    }

    /**
     * @param  array<string, string>  $row
     * @return list<array{glide_document_id: string, glide_applicant_id: string, type_label: string, external_url: string, original_name: string}>
     */
    private function profileLinks(array $row, string $applicantId): array
    {
        $links = [];
        foreach ([
            'info/image' => 'Photo',
            'current situation/ cv' => 'CV',
            'current situation/ cover letter' => 'Lettre de motivation',
        ] as $column => $type) {
            $url = trim($row[$column] ?? '');
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $links[] = [
                'glide_document_id' => 'url:'.substr(sha1($column.'|'.$url), 0, 40),
                'glide_applicant_id' => $applicantId,
                'type_label' => $type,
                'external_url' => $this->limit($url, 1000),
                'original_name' => $type,
            ];
        }

        return $links;
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return list<array{glide_document_id: ?string, glide_applicant_id: string, type_label: string, external_url: string, original_name: string}>
     */
    private function documentLinks(array $rows): array
    {
        $links = [];
        foreach ($rows as $row) {
            if (isset($row['uploaded_at'])) {
                $converted = $this->excelDateToString($row['uploaded_at']);
                if ($converted !== null) {
                    $row['uploaded_at'] = $converted;
                }
            }
            $mapped = $this->mapper->mapDocument($row);
            if ($mapped === null) {
                if (trim($row['url'] ?? '') !== '' || trim($row['applicants unique/id'] ?? '') !== '') {
                    $links[] = ['invalid' => true];
                }

                continue;
            }
            $links[] = $mapped;
        }

        return $links;
    }

    /**
     * @param  list<array<string, mixed>>  $links
     * @param  array<string, int>  $idMap
     * @param  array<string, mixed>  $report
     */
    private function importLinks(
        array $links,
        bool $dryRun,
        bool $download,
        ?int $downloadLimit,
        bool $createLookups,
        User $user,
        array $idMap,
        array &$report,
        mixed $progress
    ): void {
        if ($links === []) {
            return;
        }

        $existingByGlide = [];
        $existingByUrl = [];
        foreach (CrmCandidateDocument::query()->get(['id', 'crm_candidate_id', 'glide_document_id', 'external_url', 'path']) as $doc) {
            if (filled($doc->glide_document_id)) {
                $existingByGlide[$doc->glide_document_id] = $doc;
            }
            if (filled($doc->external_url)) {
                $existingByUrl[$doc->crm_candidate_id.'|'.$doc->external_url] = $doc;
            }
        }

        $typeIds = [];
        foreach (CrmDocumentType::query()->get(['id', 'label']) as $type) {
            $typeIds[mb_strtolower($type->label)] = $type->id;
        }
        $maxTypePosition = (int) CrmDocumentType::max('position');
        $downloaded = 0;
        $total = count($links);

        foreach ($links as $index => $link) {
            if ($progress) {
                $progress('documents', $index + 1, $total);
            }

            if (! empty($link['invalid'])) {
                $report['documents_skipped_invalid']++;

                continue;
            }

            $candidateId = $idMap[$link['glide_applicant_id']] ?? null;
            if (! $candidateId || $candidateId < 1) {
                if ($dryRun && $candidateId === -1) {
                    $candidateId = -1;
                } else {
                    $report['documents_skipped_unmatched']++;

                    continue;
                }
            }

            $typeKey = mb_strtolower($link['type_label']);
            $glideDocId = $link['glide_document_id'] ?: null;
            $existing = $glideDocId ? ($existingByGlide[$glideDocId] ?? null) : null;
            if (! $existing) {
                $existing = $existingByUrl[$candidateId.'|'.$link['external_url']] ?? null;
            }

            $fileAlreadyStored = $existing
                && filled($existing->path)
                && Storage::disk('local')->exists($existing->path);

            if ($fileAlreadyStored) {
                $report['documents_skipped_existing']++;

                continue;
            }

            $shouldDownload = $download && ! $dryRun && $candidateId > 0;
            if ($shouldDownload && $downloadLimit !== null && $downloaded >= $downloadLimit) {
                $report['documents_deferred']++;

                continue;
            }

            if ($dryRun) {
                $existing ? $report['documents_updated']++ : $report['documents_created']++;
                if ($download) {
                    $report['documents_downloaded']++;
                }

                continue;
            }

            if (! isset($typeIds[$typeKey])) {
                if (! $createLookups) {
                    $report['documents_skipped_invalid']++;
                    $this->pushError($report, 'Type de document inconnu : '.$link['type_label']);

                    continue;
                }
                $maxTypePosition++;
                $created = CrmDocumentType::create([
                    'label' => $link['type_label'],
                    'is_required' => false,
                    'actif' => true,
                    'position' => $maxTypePosition,
                ]);
                $typeIds[$typeKey] = $created->id;
                $report['document_types_created']++;
            }

            $stored = null;
            if ($shouldDownload) {
                $stored = $this->download($link['external_url'], (int) $candidateId, $link['original_name']);
                $downloaded++;
                if ($stored === null) {
                    $report['documents_download_failed']++;
                    $this->pushError($report, 'Téléchargement impossible : '.$link['external_url']);
                } else {
                    $report['documents_downloaded']++;
                }
            }

            $payload = [
                'crm_candidate_id' => $candidateId,
                'crm_document_type_id' => $typeIds[$typeKey],
                'path' => $stored['path'] ?? '',
                'external_url' => $link['external_url'],
                'original_name' => $stored['original_name'] ?? $link['original_name'],
                'mime' => $stored['mime'] ?? $this->mimeFromUrl($link['external_url']),
                'size' => $stored['size'] ?? null,
                'uploaded_by' => $user->id,
                'glide_document_id' => $glideDocId,
            ];

            if ($existing) {
                $existing->fill($payload)->save();
                $report['documents_updated']++;
            } else {
                $doc = CrmCandidateDocument::create($payload);
                if ($glideDocId) {
                    $existingByGlide[$glideDocId] = $doc;
                }
                $existingByUrl[$candidateId.'|'.$link['external_url']] = $doc;
                $report['documents_created']++;
            }
        }
    }

    /**
     * @return array{path: string, mime: string, size: int, original_name: string}|null
     */
    private function download(string $url, int $candidateId, string $originalName): ?array
    {
        if (! $this->isPublicHttpUrl($url)) {
            return null;
        }

        try {
            $response = Http::retry(2, 200)
                ->timeout(40)
                ->connectTimeout(10)
                ->withHeaders(['User-Agent' => 'ESCM-CRM-Import'])
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();
        $size = strlen($body);
        if ($size < 1 || $size > self::MAX_BYTES) {
            return null;
        }

        $sample = substr($body, 0, 200);
        if (preg_match('/<!DOCTYPE|<html/i', $sample)) {
            return null;
        }

        $mime = $this->detectMime($body, (string) $response->header('Content-Type'), $url);
        $extension = $this->extensionFor($mime, $url, $originalName);
        $originalName = $this->downloadName($originalName, $extension);
        $relative = 'crm/documents/'.$candidateId.'/'.((string) Str::uuid()).'.'.$extension;
        Storage::disk('local')->put($relative, $body);

        return [
            'path' => $relative,
            'mime' => $this->limit($mime, 120),
            'size' => $size,
            'original_name' => $originalName,
        ];
    }

    private function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        return ! in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true);
    }

    private function detectMime(string $body, string $header, string $url): string
    {
        if (str_starts_with($body, '%PDF')) {
            return 'application/pdf';
        }
        if (str_starts_with($body, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($body, "\x89PNG")) {
            return 'image/png';
        }

        $header = strtolower(trim(strtok($header, ';') ?: ''));
        if ($header !== '' && ! in_array($header, ['application/octet-stream', 'text/html'], true)) {
            return $header;
        }

        return $this->mimeFromUrl($url) ?? 'application/octet-stream';
    }

    private function extensionFor(string $mime, string $url, string $originalName): string
    {
        $fromName = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx'];
        if (in_array($fromName, $allowed, true)) {
            return $fromName === 'jpeg' ? 'jpg' : $fromName;
        }

        $fromUrl = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (in_array($fromUrl, $allowed, true)) {
            return $fromUrl === 'jpeg' ? 'jpg' : $fromUrl;
        }

        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    private function downloadName(string $name, string $extension): string
    {
        $name = str_replace(["\r", "\n", '"', '/', '\\'], '', trim($name));
        if ($name === '') {
            $name = 'document.'.$extension;
        } elseif (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === '') {
            $name .= '.'.$extension;
        }

        return $this->limit($name, 255);
    }

    private function mimeFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };
    }

    /**
     * @param  array<string, int>  $map
     * @param  array<string, mixed>  $mapped
     */
    private function rememberCandidate(array &$map, array $mapped, int $id): void
    {
        $map[$mapped['glide_applicant_id']] = $id;
        if (! empty($mapped['glide_row_id'])) {
            $map[$mapped['glide_row_id']] = $id;
        }
    }

    /**
     * @return array<string, int>
     */
    private function candidateIdMap(): array
    {
        $map = [];
        CrmCandidate::query()
            ->where(function ($query) {
                $query->whereNotNull('glide_applicant_id')->orWhereNotNull('glide_row_id');
            })
            ->get(['id', 'glide_applicant_id', 'glide_row_id'])
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
     * @return array<string, int>
     */
    private function advisorIndex(): array
    {
        $index = [];
        foreach (User::query()->get(['id', 'name', 'email']) as $user) {
            $index[$this->mapper->normalizePersonName($user->name)] = $user->id;
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
        $existingLower = [];
        foreach ($model::query()->pluck('label') as $label) {
            $existingLower[mb_strtolower($label)] = true;
        }
        $max = (int) $model::max('position');

        foreach ($labels as $label) {
            if ($label === '' || isset($existingLower[mb_strtolower($label)])) {
                continue;
            }
            $created++;
            $existingLower[mb_strtolower($label)] = true;
            if ($dryRun) {
                continue;
            }
            $max++;
            $model::create([
                'label' => $label,
                'actif' => true,
                'position' => $max,
            ]);
        }

        return $created;
    }

    public function excelDateToString(string $value): ?string
    {
        $value = trim($value);
        if (! preg_match('/^\d+(\.\d+)?$/', $value)) {
            return null;
        }

        $serial = (float) $value;
        if ($serial < 20000 || $serial > 80000) {
            return null;
        }

        $whole = (int) floor($serial);
        $seconds = (int) round(($serial - $whole) * 86400);
        $timestamp = ($whole - 25569) * 86400 + $seconds;
        $formatted = gmdate('Y-m-d H:i:s', $timestamp);

        return str_ends_with($formatted, '00:00:00')
            ? substr($formatted, 0, 10)
            : $formatted;
    }

    private function isOpaqueId(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || preg_match('/\s/u', $value)) {
            return false;
        }
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return true;
        }
        if (strlen($value) < 16 || strlen($value) > 40 || ! preg_match('/^[A-Za-z0-9._\-]+$/', $value)) {
            return false;
        }

        return preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value)
            && (preg_match('/\d/', $value) || strpbrk($value, '._-') !== false);
    }

    private function looksLikePersonName(string $value): bool
    {
        if ($value === '' || $this->isOpaqueId($value) || preg_match('/\d/u', $value)) {
            return false;
        }

        return preg_match('/^\p{L}+(?:[ \'-]\p{L}+)*$/u', $value) === 1;
    }

    private function readable(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $this->isOpaqueId($value)) {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            $number = (float) $value;
            if ($number >= 1900 && $number <= 2100) {
                return (string) (int) $number;
            }

            return '';
        }

        return $value;
    }

    private function limit(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function looksLikeDocuments(array $row): bool
    {
        return array_key_exists('applicants unique/id', $row)
            && (array_key_exists('url', $row) || array_key_exists('type', $row))
            && ! array_key_exists('info/first name', $row);
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
            'documents_created' => 0,
            'documents_updated' => 0,
            'documents_skipped_existing' => 0,
            'documents_skipped_unmatched' => 0,
            'documents_skipped_invalid' => 0,
            'documents_downloaded' => 0,
            'documents_download_failed' => 0,
            'documents_deferred' => 0,
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
        if (count($report['errors']) >= 30) {
            return;
        }
        $report['errors'][] = $message;
    }
}
