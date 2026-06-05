<?php

declare(strict_types=1);

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Thin wrapper around `openspout/openspout` to produce a single-sheet
 * XLSX (Office Open XML SpreadsheetML) download.
 *
 * Why a wrapper:
 *  - Centralises the response headers (`Content-Type`, `Content-Disposition`,
 *    `Content-Length`, cache control) in one place.
 *  - Sanitises the supplied filename (only `[A-Za-z0-9_-]` allowed).
 *  - Lazily constructs the OpenSpout writer so callers can simply
 *    `addRow()` / `addRows()` and then call `download()` or `toString()`.
 *
 * Usage:
 *
 *     $writer = new XlsxWriter();
 *     $writer->setTitle('Laporan X-A');
 *     $writer->addRow(['NIS', 'Nama', 'Nilai']);
 *     $writer->addRow(['00001', 'Ahmad', 80]);
 *     $writer->download('laporan');
 */
class XlsxWriter
{
    /** @var array<int, array<int, string|int|float|null>> */
    private array $rows = [];

    private ?string $title = null;

    /**
     * Set the human-readable title used for the worksheet tab name.
     *
     * @param  string  $title  The title string (will be HTML-escaped by OpenSpout).
     * @return self Allows fluent chaining.
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Append a single row to the workbook.
     *
     * @param  array<int, string|int|float|null>  $row  The cell values for the new row.
     * @return self Allows fluent chaining.
     */
    public function addRow(array $row): self
    {
        $this->rows[] = array_values($row);

        return $this;
    }

    /**
     * Append multiple rows in one call.
     *
     * @param  iterable<int, array<int, string|int|float|null>>  $rows  Iterable of row arrays.
     * @return self Allows fluent chaining.
     */
    public function addRows(iterable $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * Stream the workbook as an HTTP download and terminate the script.
     *
     * Filenames are sanitised to `[A-Za-z0-9_-]`; the `.xlsx` extension is
     * appended automatically. Sends the standard `Content-Type` and
     * `Content-Disposition` headers expected by browsers.
     *
     * @param  string  $filename  The base filename (no extension).
     */
    public function download(string $filename): void
    {
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) ?: 'export';

        $binary = $this->toString();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'.xlsx"');
        header('Content-Length: '.(string) strlen($binary));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $binary;
        exit;
    }

    /**
     * Build the workbook in memory and return the binary XLSX payload.
     *
     * @return string The raw `.xlsx` bytes, ready to be written to a file or streamed.
     *
     * @throws \RuntimeException When the OpenSpout writer fails to open the temporary file.
     */
    public function toString(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tempFile === false) {
            throw new \RuntimeException('Gagal membuat file XLSX sementara.');
        }

        $writer = new Writer;
        $writer->openToFile($tempFile);

        if ($this->title !== null) {
            $sheet = $writer->getCurrentSheet();
            if ($sheet instanceof Sheet) {
                $sheet->setName($this->title);
            }
        }

        foreach ($this->rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        $contents = file_get_contents($tempFile);
        @unlink($tempFile);

        if ($contents === false) {
            throw new \RuntimeException('Gagal membaca file XLSX sementara.');
        }

        return $contents;
    }
}
