<?php

namespace App\Services\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\User;
use Throwable;

class CrmCandidateDocumentImporter
{
    public function __construct(
        private readonly GlideFileFetcher $files,
        private readonly SimpleXlsxReader $xlsx,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $path, User $user): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $report = [
            'documents_created' => 0,
            'documents_already_local' => 0,
            'documents_skipped_unmatched' => 0,
            'documents_skipped_invalid' => 0,
            'documents_skipped_download' => 0,
            'document_types_created' => 0,
            'errors' => [],
        ];

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $rows = in_array($extension, ['xlsx', 'xlsm'], true)
            ? $this->xlsx->rows($path)
            : GlideCsv::rows($path);

        if ($rows === []) {
            $report['errors'][] = 'Le fichier ne contient aucune ligne de document.';

            return $report;
        }

        $existingDocs = CrmCandidateDocument::query()
            ->whereNotNull('glide_document_id')
            ->get(['id', 'glide_document_id', 'path'])
            ->keyBy('glide_document_id');

        $typeIds = [];
        foreach (CrmDocumentType::query()->get(['id', 'label']) as $type) {
            $typeIds[mb_strtolower($type->label)] = $type->id;
        }
        $maxTypePosition = (int) CrmDocumentType::max('position');

        foreach ($rows as $row) {
            $candidateId = $this->candidateId($this->value($row, 'applicants unique/id', 'id'));
            $typeLabel = $this->value($row, 'type');
            $url = $this->value($row, 'url');
            $rowId = $this->value($row, 'row id');

            if ($candidateId === '' || ! ctype_digit($candidateId) || $typeLabel === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                $report['documents_skipped_invalid']++;
                $this->pushError($report, 'Ligne ignorée : ID candidat, type ou URL manquant.');

                continue;
            }

            $candidate = CrmCandidate::query()->find((int) $candidateId);
            if (! $candidate) {
                $report['documents_skipped_unmatched']++;
                $this->pushError($report, 'Candidat introuvable pour l’ID '.$candidateId.'.');

                continue;
            }

            $existing = $rowId !== '' ? $existingDocs->get($rowId) : null;
            if ($existing && filled($existing->path)) {
                $report['documents_already_local']++;

                continue;
            }

            $typeKey = mb_strtolower($typeLabel);
            if (! isset($typeIds[$typeKey])) {
                $maxTypePosition++;
                $created = CrmDocumentType::create([
                    'label' => mb_substr($typeLabel, 0, 255),
                    'is_required' => false,
                    'actif' => true,
                    'position' => $maxTypePosition,
                ]);
                $typeIds[$typeKey] = $created->id;
                $report['document_types_created']++;
            }

            $name = $this->value($row, 'name');
            if ($name === '') {
                $name = $typeLabel;
            }

            try {
                $stored = $this->files->store($url, $candidate->id, $name);
            } catch (Throwable $e) {
                $report['documents_skipped_download']++;
                $this->pushError($report, 'Document non téléchargé ('.$typeLabel.', candidat '.$candidateId.') : '.$e->getMessage());

                continue;
            }

            try {
                $document = CrmCandidateDocument::create([
                    'crm_candidate_id' => $candidate->id,
                    'crm_document_type_id' => $typeIds[$typeKey],
                    'path' => $stored['path'],
                    'external_url' => null,
                    'original_name' => $stored['original_name'],
                    'mime' => $stored['mime'],
                    'size' => $stored['size'],
                    'uploaded_by' => $user->id,
                    'glide_document_id' => $rowId !== '' ? mb_substr($rowId, 0, 80) : null,
                ]);
                if ($rowId !== '') {
                    $existingDocs->put($rowId, $document);
                }
                $report['documents_created']++;
            } catch (Throwable $e) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($stored['path']);
                $report['documents_skipped_download']++;
                $this->pushError($report, 'Document non enregistré ('.$typeLabel.', candidat '.$candidateId.').');
            }
        }

        return $report;
    }

    private function candidateId(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d+\.0+$/', $value) === 1) {
            return (string) (int) $value;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function value(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            $normalized = GlideCsv::normalizeHeader($key);
            if (isset($row[$normalized]) && trim($row[$normalized]) !== '') {
                return trim($row[$normalized]);
            }
        }

        return '';
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
