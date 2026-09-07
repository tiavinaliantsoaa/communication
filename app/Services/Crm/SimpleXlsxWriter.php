<?php

namespace App\Services\Crm;

use ZipArchive;

class SimpleXlsxWriter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function build(array $headers, array $rows, string $sheetName = 'Candidats'): string
    {
        $sheetName = $this->sanitizeSheetName($sheetName);
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new \RuntimeException('Impossible de créer le fichier Excel temporaire.');
        }
        unlink($tmp);

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Impossible de générer le fichier Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($headers, $rows));
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        if ($binary === false) {
            throw new \RuntimeException('Impossible de lire le fichier Excel généré.');
        }

        return $binary;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function sheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        $xml .= $this->rowXml(1, $headers);

        foreach ($rows as $index => $row) {
            $xml .= $this->rowXml($index + 2, $row);
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    /**
     * @param  list<string>  $values
     */
    private function rowXml(int $rowNumber, array $values): string
    {
        $cells = '';
        foreach (array_values($values) as $col => $value) {
            $ref = $this->columnLetter($col + 1).$rowNumber;
            $cells .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xmlText((string) $value).'</t></is></c>';
        }

        return '<row r="'.$rowNumber.'">'.$cells.'</row>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function xmlText(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);

        return htmlspecialchars(mb_substr($value, 0, 32000), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeSheetName(string $name): string
    {
        $name = str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $name);
        $name = trim($name);

        return $name === '' ? 'Candidats' : mb_substr($name, 0, 31);
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->xmlText($sheetName).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
