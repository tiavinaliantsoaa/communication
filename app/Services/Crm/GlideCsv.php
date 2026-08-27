<?php

namespace App\Services\Crm;

class GlideCsv
{
    /**
     * @return list<array<string, string>>
     */
    public static function rows(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Impossible de lire le fichier CSV.');
        }

        try {
            $headerLine = fgetcsv($handle);
            if (! is_array($headerLine) || $headerLine === []) {
                return [];
            }

            $delimiter = ',';
            if (count($headerLine) === 1 && str_contains((string) $headerLine[0], ';')) {
                rewind($handle);
                $delimiter = ';';
                $headerLine = fgetcsv($handle, 0, $delimiter);
                if (! is_array($headerLine)) {
                    return [];
                }
            }

            $headerLine[0] = preg_replace('/^\xEF\xBB\xBF/u', '', (string) ($headerLine[0] ?? '')) ?? (string) $headerLine[0];
            $headers = array_map([self::class, 'normalizeHeader'], $headerLine);

            $rows = [];
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($data === [null] || $data === false) {
                    continue;
                }

                $row = [];
                foreach ($headers as $i => $header) {
                    if ($header === '') {
                        continue;
                    }
                    $row[$header] = isset($data[$i]) ? trim((string) $data[$i]) : '';
                }

                if (self::rowIsEmpty($row)) {
                    continue;
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $headers
     */
    public static function kindFromHeaders(array $headers): ?string
    {
        $normalized = array_map([self::class, 'normalizeHeader'], $headers);
        $set = array_flip($normalized);

        $isDocuments = isset($set['applicants unique/id']) && (isset($set['url']) || isset($set['type']));
        $isCandidates = isset($set['unique applicants id']) || isset($set['info/first name']) || isset($set['info/full name']);

        if ($isDocuments && ! $isCandidates) {
            return 'documents';
        }
        if ($isCandidates && ! $isDocuments) {
            return 'candidates';
        }
        if ($isDocuments) {
            return 'documents';
        }
        if ($isCandidates) {
            return 'candidates';
        }

        return null;
    }

    public static function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $header = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $header) ?? $header;
        $header = trim(preg_replace('/\s+/u', ' ', $header) ?? $header);

        return mb_strtolower($header);
    }

    /**
     * @param  array<string, string>  $row
     */
    private static function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
