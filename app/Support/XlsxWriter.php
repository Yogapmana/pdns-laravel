<?php

namespace App\Support;

/**
 * Minimal XLSX (OpenXML SpreadsheetML) writer — pure PHP, no PhpSpreadsheet.
 *
 * Generates a single-sheet XLSX with inline strings (no sharedStrings.xml).
 * Output: Office Open XML SpreadsheetML (.xlsx) — opens in Excel, LibreOffice,
 * Google Sheets. Uses ZipArchive + simplexml.
 *
 * Usage:
 *   $writer = new XlsxWriter();
 *   $writer->addRow(['NIS', 'Nama', 'Nilai']);
 *   $writer->addRow(['00001', 'Ahmad', 80]);
 *   $writer->download('laporan');
 */
class XlsxWriter
{
    /** @var array<int, array<int, string|int|float|null>> */
    private array $rows = [];

    private ?string $title = null;

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function addRow(array $row): self
    {
        $this->rows[] = array_values($row);

        return $this;
    }

    /**
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public function addRows(iterable $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    public function download(string $filename): void
    {
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) ?: 'export';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'.xlsx"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $this->build();
        exit;
    }

    public function toString(): string
    {
        return $this->build();
    }

    private function build(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $zip = new \ZipArchive;
        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file XLSX sementara.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());

        $zip->close();

        $contents = file_get_contents($tempFile);
        @unlink($tempFile);

        if ($contents === false) {
            throw new \RuntimeException('Gagal membaca file XLSX sementara.');
        }

        return $contents;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
            '<Default Extension="xml" ContentType="application/xml"/>'.
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'.
            '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'.
            '</Relationships>';
    }

    private function workbook(): string
    {
        $title = htmlspecialchars($this->title ?? 'Sheet1', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.
            '<workbookPr date1904="false"/>'.
            '<sheets>'.
            '<sheet name="'.$title.'" sheetId="1" r:id="rId1"/>'.
            '</sheets>'.
            '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'.
            '</Relationships>';
    }

    private function sheet(): string
    {
        $rows = '';
        $rowIndex = 1;
        foreach ($this->rows as $row) {
            $cells = '';
            $colIndex = 1;
            foreach ($row as $value) {
                $ref = $this->cellRef($colIndex, $rowIndex);
                $cells .= $this->buildCell($ref, $value);
                $colIndex++;
            }
            $rows .= '<row r="'.$rowIndex.'">'.$cells.'</row>';
            $rowIndex++;
        }

        $dimension = $rowIndex > 1 ? 'A1:'.$this->cellRef(max(1, count($this->rows[0] ?? [])), $rowIndex - 1) : 'A1';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.
            '<dimension ref="'.$dimension.'"/>'.
            '<sheetData>'.$rows.'</sheetData>'.
            '</worksheet>';
    }

    private function buildCell(string $ref, string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '<c r="'.$ref.'"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'"><v>'.$value.'</v></c>';
        }

        $escaped = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $inlineStr = '<is><t xml:space="preserve">'.$escaped.'</t></is>';

        return '<c r="'.$ref.'" t="inlineStr">'.$inlineStr.'</c>';
    }

    private function cellRef(int $col, int $row): string
    {
        $letters = '';
        while ($col > 0) {
            $col--;
            $letters = chr(65 + ($col % 26)).$letters;
            $col = intdiv($col, 26);
        }

        return $letters.$row;
    }
}
