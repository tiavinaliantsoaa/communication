<?php

namespace App\Services\Crm;

use App\Models\CrmCandidate;
use Carbon\Carbon;

class GlideFieldMapper
{
    /**
     * @param  array<string, string>  $row
     * @param  array<string, int>  $advisorIdsByName  lowercase name => user id
     * @return array<string, mixed>|null
     */
    public function mapCandidate(array $row, array $advisorIdsByName = []): ?array
    {
        $glideId = $this->first($row, 'unique applicants id', 'final applicants id', 'row id');
        $rowId = $this->first($row, 'row id', 'unique applicants id', 'final applicants id');

        $fullName = $this->first($row, 'info/full name');
        $prenom = $this->first($row, 'info/first name');
        $nom = $this->first($row, 'info/last name', 'info/ last name start case');

        if ($prenom === '' && $nom === '' && $fullName !== '') {
            $parts = preg_split('/\s+/', $fullName) ?: [];
            $prenom = (string) array_shift($parts);
            $nom = trim(implode(' ', $parts));
        }

        $prenom = $this->limit($prenom, 100);
        $nom = $this->limit($nom, 100);

        if ($prenom === '' && $nom === '') {
            return null;
        }
        if ($prenom === '') {
            $prenom = '—';
        }
        if ($nom === '') {
            $nom = '—';
        }
        if ($glideId === '') {
            return null;
        }

        $sourceRaw = $this->first($row, 'sources/ name');
        $sourceType = $this->first($row, 'sources/ type');
        [$source, $tourVille] = $this->mapSource($sourceRaw, $sourceType);

        if ($this->isTruthy($this->first($row, 'details/jpo')) && $source === null) {
            $source = 'salon';
        }

        $facebookUrl = $this->limit($this->first($row, 'info/page facebook'), 500);
        if ($source !== 'facebook') {
            $facebookUrl = null;
        } elseif ($facebookUrl === '') {
            $facebookUrl = null;
        }

        if ($source !== 'escm_tour') {
            $tourVille = null;
        }

        $parents = $this->splitParents($this->first($row, 'téléphone des parents', 'telephone des parents'));

        $programme = $this->firstListItem($this->first($row, 'wish/ filiere'));
        if ($programme === '') {
            $programme = $this->firstListItem($this->first($row, 'applications/ all program name list'));
        }
        $programme = $this->limit($programme, 255) ?: null;

        $intake = $this->firstListItem($this->first($row, 'applications/ all academic year'));
        if ($intake === '') {
            $intake = $this->firstListItem($this->first($row, 'wish/ academic years'));
        }
        $intake = $this->limit($intake, 50) ?: null;

        $abandon = $this->isTruthy($this->first($row, 'abandon/ is abandon'))
            || $this->statusKey($this->first($row, 'status display')) === 'abandon';

        $frais = $this->isTruthy($this->first(
            $row,
            'status step 1/ is application fees payed',
            'checkapplications/ application fees is payed'
        ));
        $validation = $this->isTruthy($this->first(
            $row,
            'status step 2/ is evaluation grade received',
            'status step 2/ is favorable opinion'
        ));
        $lettre = $this->isTruthy($this->first($row, 'status step 3/ letter admission uploaded'));
        $acompte = $this->isTruthy($this->first($row, 'checkinscription/ account payed'));
        $totalite = $this->isTruthy($this->first($row, 'checkinscription/ scholl fees paid'));

        $this->applyStatusHints($this->first($row, 'status display'), $frais, $validation, $lettre, $acompte, $totalite);

        $notes = $this->buildNotes($row, $source, $sourceRaw, $abandon);

        $email = $this->sanitizeEmail($this->first($row, 'info/ email', 'champ adresse mail'));
        $createdAt = $this->parseDateTime($this->first($row, 'created_at'));

        $advisorName = $this->first($row, 'responsible/ name');
        $advisorId = $advisorIdsByName[$this->normalizePersonName($advisorName)] ?? null;

        return [
            'glide_applicant_id' => $this->limit($glideId, 80),
            'glide_row_id' => $this->limit($rowId, 80) ?: null,
            'prenom' => $prenom,
            'nom' => $nom,
            'genre' => $this->mapGenre($this->first($row, 'info/gender', 'info/civility')),
            'date_naissance' => $this->parseDate($this->first($row, 'info/ date of birth', 'info/date of birth')),
            'telephone' => $this->limit($this->first($row, 'info/phone number', 'info/champ phone number'), 40) ?: null,
            'email' => $email,
            'adresse' => $this->buildAddress($row),
            'contact_parent_1' => $this->limit($parents[0] ?? '', 80) ?: null,
            'contact_parent_2' => $this->limit($parents[1] ?? '', 80) ?: null,
            'programme' => $programme,
            'annee_academique' => $intake,
            'niveau_etudes' => $this->limit($this->first($row, 'current situation/ level'), 100) ?: null,
            'etablissement_origine' => $this->limit($this->first($row, 'current situation/ name'), 255) ?: null,
            'source' => $source,
            'facebook_profil_url' => $facebookUrl,
            'escm_tour_ville' => $this->limit((string) $tourVille, 120) ?: null,
            'advisor_id' => $advisorId,
            'notes' => $notes,
            'paiement_frais_test' => $frais,
            'validation_test' => $validation,
            'lettre_admission' => $lettre,
            'paiement_acompte' => $acompte,
            'paiement_totalite' => $totalite,
            'abandon' => $abandon,
            'abandon_raison' => $this->limit($this->first($row, 'abandon/ reason display'), 255) ?: null,
            'created_at' => $createdAt,
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>|null
     */
    public function mapDocument(array $row): ?array
    {
        $glideDocId = $this->first($row, 'row id');
        $applicantId = $this->first($row, 'applicants unique/id');
        $url = $this->first($row, 'url');
        $type = $this->first($row, 'type');

        if ($applicantId === '' || $url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        if ($type === '') {
            $type = 'Document';
        }

        $name = $this->first($row, 'name');
        if ($name === '') {
            $name = basename(parse_url($url, PHP_URL_PATH) ?: $type);
        }

        return [
            'glide_document_id' => $this->limit($glideDocId, 80) ?: null,
            'glide_applicant_id' => $this->limit($applicantId, 80),
            'type_label' => $this->limit($type, 255),
            'external_url' => $this->limit($url, 1000),
            'original_name' => $this->limit($name, 255),
            'mime' => $this->mimeFromUrl($url),
            'uploaded_at' => $this->parseDateTime($this->first($row, 'uploaded_at')),
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    public function first(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            $normalized = GlideCsv::normalizeHeader($key);
            if (isset($row[$normalized]) && trim($row[$normalized]) !== '') {
                return trim($row[$normalized]);
            }
        }

        return '';
    }

    public function normalizePersonName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        return mb_strtolower($name);
    }

    public function isTruthy(string $value): bool
    {
        $value = mb_strtolower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'oui', 'vrai', 'on'], true);
    }

    /**
     * @param  array<string, string>  $row
     */
    public function matchesYearFilter(array $row, ?string $filter): bool
    {
        if ($filter === null || $filter === '' || $filter === 'all') {
            return true;
        }

        $years = match ($filter) {
            '2025-2026' => ['2025', '2026'],
            default => [],
        };
        if ($years === []) {
            return true;
        }

        $haystack = implode(' ', [
            $this->first($row, 'applications/ all academic year'),
            $this->first($row, 'wish/ academic years'),
            $this->first($row, 'applications/ all program name list'),
            $this->first($row, 'template/ program + promotion + current level'),
            $this->first($row, 'année de paiement', 'annee de paiement'),
        ]);

        foreach ($years as $year) {
            if (preg_match('/\b'.preg_quote($year, '/').'\b/', $haystack) === 1) {
                return true;
            }
        }

        return false;
    }

    private function mapGenre(string $value): ?string
    {
        $value = mb_strtolower(trim($value));
        if (in_array($value, ['homme', 'monsieur', 'm', 'male', 'h'], true)) {
            return 'homme';
        }
        if (in_array($value, ['femme', 'madame', 'mademoiselle', 'mme', 'mlle', 'female', 'f'], true)) {
            return 'femme';
        }
        if ($value === '') {
            return null;
        }

        return 'autre';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function mapSource(string $name, string $type): array
    {
        $n = mb_strtolower($name);
        $t = mb_strtolower($type);

        if ($n === '' && $t === '') {
            return [null, null];
        }

        if (str_contains($n, 'facebook') || str_contains($n, 'instagram')) {
            return ['facebook', null];
        }
        if (str_contains($n, 'site web') || $t === 'site web') {
            return ['site_web', null];
        }
        if (str_contains($n, 'tour')) {
            $ville = trim(preg_replace('/\b(escm\s+)?tour\b/iu', '', $name) ?? $name);

            return ['escm_tour', $ville !== '' ? $ville : null];
        }
        if (
            str_contains($n, 'salon')
            || str_contains($n, 'forum')
            || $n === 'jpo'
            || str_contains($n, 'fim')
            || str_contains($n, 'visite lyc')
        ) {
            return ['salon', null];
        }

        return [null, null];
    }

    private function statusKey(string $statusDisplay): string
    {
        $clean = trim(preg_replace('/^[^\p{L}]+/u', '', $statusDisplay) ?? $statusDisplay);

        return mb_strtolower($clean);
    }

    private function applyStatusHints(
        string $statusDisplay,
        bool &$frais,
        bool &$validation,
        bool &$lettre,
        bool &$acompte,
        bool &$totalite
    ): void {
        $key = $this->statusKey($statusDisplay);

        if (str_contains($key, 'inscrit')) {
            if (! $totalite) {
                $acompte = true;
            }
        } elseif (str_contains($key, 'inscription')) {
            $validation = true;
            $lettre = true;
        } elseif (str_contains($key, 'évaluation') || str_contains($key, 'evaluation')) {
            $validation = true;
        } elseif (str_contains($key, 'intention') || str_contains($key, 'candidature déposée') || str_contains($key, 'candidature deposee') || str_contains($key, 'découverte') || str_contains($key, 'decouverte')) {
            $frais = true;
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    private function buildNotes(array $row, ?string $source, string $sourceRaw, bool $abandon): ?string
    {
        $parts = [];

        if ($abandon) {
            $reason = $this->first($row, 'abandon/ reason display');
            $parts[] = $reason !== '' ? 'Abandon : '.$reason : 'Candidature abandonnée (Glide).';
        }

        if ($source === null && $sourceRaw !== '') {
            $parts[] = 'Source Glide : '.$sourceRaw;
        }

        $escmId = $this->first($row, 'info/ id escm', 'immatriculation/ id escm');
        if ($escmId !== '') {
            $parts[] = 'ID ESCM : '.$escmId;
        }

        $comments = $this->first($row, 'evaluations/ comment list');
        if ($comments !== '') {
            $comments = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $comments)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($comments !== '') {
                $parts[] = 'Évaluation : '.$comments;
            }
        }

        $place = $this->first($row, 'info/ place of birth');
        if ($place !== '') {
            $parts[] = 'Lieu de naissance : '.$place;
        }

        $notes = trim(implode("\n", $parts));
        if ($notes === '') {
            return null;
        }

        return $this->limit($notes, 5000);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function buildAddress(array $row): ?string
    {
        $parts = array_filter([
            $this->first($row, 'localisation/ address'),
            $this->first($row, 'localisation/ city'),
            $this->first($row, 'localisation/province'),
        ], fn ($v) => $v !== '');

        if ($parts === []) {
            return null;
        }

        return $this->limit(implode(', ', array_unique($parts)), 1000);
    }

    /**
     * @return list<string>
     */
    private function splitParents(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s*\|\s*|\s+-\s+/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== '' && $v !== '-'));
    }

    private function firstListItem(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', $value));

        return $parts[0] ?? '';
    }

    private function sanitizeEmail(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || in_array(mb_strtolower($value), ['pas d\'adresse mail', 'adresse mail valide'], true)) {
            return null;
        }
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $this->limit($value, 255);
    }

    private function parseDate(string $value): ?string
    {
        $dt = $this->parseDateTime($value);

        return $dt?->format('Y-m-d');
    }

    private function parseDateTime(string $value): ?Carbon
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        if ($value === '' || preg_match('/[A-Za-zÀ-ÿ]/u', $value)) {
            return null;
        }

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y G:i:s',
            'j/n/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'j/n/Y',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt;
                }
            } catch (\Throwable) {
                // try next
            }
        }

        return null;
    }

    private function mimeFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };
    }

    private function limit(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max);
    }

    /**
     * Kept so pipeline flags stay consistent with the CRM resolver.
     *
     * @param  array<string, mixed>  $attrs
     */
    public function resolveStatut(array $attrs): string
    {
        return CrmCandidate::resolveStatutFromAttributes($attrs);
    }
}
