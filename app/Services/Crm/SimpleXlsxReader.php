<?php

namespace App\Services\Crm;

use ZipArchive;

class SimpleXlsxReader
{
    /**
     * Première feuille, une ligne associative par enregistrement.
     * Les en-têtes sont normalisés comme les exports Glide.
     *
     * @return list<array<string, string>>
     */
    public function rows(string $path): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException('Fichier Excel introuvable : '.$path);
        }
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('L’extension PHP zip est requise pour lire un fichier Excel. Activez extension=zip.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Impossible d’ouvrir le fichier Excel : '.$path);
        }

        try {
            $strings = $this->sharedStrings($zip);
            $sheetXml = $this->firstSheet($zip);
        } finally {
            $zip->close();
        }

        if ($sheetXml === null) {
            throw new \RuntimeException('Le fichier Excel ne contient aucune feuille : '.$path);
        }

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false || ! isset($sheet->sheetData->row)) {
            return [];
        }

        $header = null;
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = $this->rowCells($row, $strings);
            if ($header === null) {
                $header = [];
                foreach ($cells as $column => $value) {
                    $name = GlideCsv::normalizeHeader($value);
                    if ($name !== '') {
                        $header[$column] = $name;
                    }
                }

                continue;
            }

            $assoc = [];
            foreach ($header as $column => $name) {
                $assoc[$name] = trim($cells[$column] ?? '');
            }
            if ($this->isEmpty($assoc)) {
                continue;
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false || $xml === '') {
            return [];
        }

        $sheet = simplexml_load_string($xml);
        if ($sheet === false) {
            return [];
        }

        $strings = [];
        foreach ($sheet->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;

                continue;
            }
            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function firstSheet(ZipArchive $zip): ?string
    {
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($xml !== false && $xml !== '') {
            return $xml;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                $xml = $zip->getFromName($name);

                return ($xml !== false && $xml !== '') ? $xml : null;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $strings
     * @return array<string, string>
     */
    private function rowCells(\SimpleXMLElement $row, array $strings): array
    {
        $cells = [];
        foreach ($row->c as $cell) {
            $column = preg_replace('/\d+/', '', (string) $cell['r']) ?? '';
            if ($column === '') {
                continue;
            }
            $cells[$column] = $this->cellValue($cell, $strings);
        }

        return $cells;
    }

    /**
     * @param  list<string>  $strings
     */
    private function cellValue(\SimpleXMLElement $cell, array $strings): string
    {
        $type = (string) $cell['t'];
        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : '';
        }

        $value = isset($cell->v) ? (string) $cell->v : '';
        if ($type === 's') {
            return $strings[(int) $value] ?? '';
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
