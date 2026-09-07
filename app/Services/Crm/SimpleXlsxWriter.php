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
        $strings = [];
        $sheetRows = [];

        $sheetRows[] = $this->indexRow($headers, $strings);
        foreach ($rows as $row) {
            $sheetRows[] = $this->indexRow($row, $strings);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new \RuntimeException('Impossible de créer le fichier Excel temporaire.');
        }
        @unlink($tmp);

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de générer le fichier Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStrings($strings));
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($sheetRows, count($headers)));
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Impossible de lire le fichier Excel généré.');
        }

        return $binary;
    }

    /**
     * @param  list<string>  $values
     * @param  list<string>  $strings
     * @return list<int>
     */
    private function indexRow(array $values, array &$strings): array
    {
        $indexes = [];
        foreach (array_values($values) as $value) {
            $text = $this->plainText((string) $value);
            $found = array_search($text, $strings, true);
            if ($found === false) {
                $strings[] = $text;
                $found = array_key_last($strings);
            }
            $indexes[] = (int) $found;
        }

        return $indexes;
    }

    /**
     * @param  list<list<int>>  $sheetRows
     */
    private function sheetXml(array $sheetRows, int $columnCount): string
    {
        $lastCol = $this->columnLetter(max(1, $columnCount));
        $lastRow = max(1, count($sheetRows));
        $cols = '';
        for ($i = 1; $i <= max(1, $columnCount); $i++) {
            $cols .= '<col min="'.$i.'" max="'.$i.'" width="22" customWidth="1"/>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:'.$lastCol.$lastRow.'"/>'
            .'<cols>'.$cols.'</cols>'
            .'<sheetData>';

        foreach ($sheetRows as $rowIndex => $indexes) {
            $rowNumber = $rowIndex + 1;
            $cells = '';
            foreach ($indexes as $col => $stringIndex) {
                $ref = $this->columnLetter($col + 1).$rowNumber;
                $cells .= '<c r="'.$ref.'" t="s"><v>'.$stringIndex.'</v></c>';
            }
            $spanEnd = max(1, count($indexes));
            $xml .= '<row r="'.$rowNumber.'" spans="1:'.$spanEnd.'">'.$cells.'</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    /**
     * @param  list<string>  $strings
     */
    private function sharedStrings(array $strings): string
    {
        $count = count($strings);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.$count.'" uniqueCount="'.$count.'">';

        foreach ($strings as $string) {
            $xml .= '<si><t xml:space="preserve">'.$this->xmlText($string).'</t></si>';
        }

        return $xml.'</sst>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            .'<fills count="2">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
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

    private function plainText(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;

        return str_replace(["\r\n", "\r", "\n"], ' ', $value);
    }

    private function xmlText(string $value): string
    {
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
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
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
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            .'</Relationships>';
    }
}
