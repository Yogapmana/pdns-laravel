<?php

declare(strict_types=1);

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Wrapper tipis di sekitar `openspout/openspout` untuk menghasilkan unduhan single-sheet
 * XLSX (Office Open XML SpreadsheetML).
 *
 * Mengapa menggunakan wrapper:
 *  - Memusatkan response header (`Content-Type`, `Content-Disposition`,
 *    `Content-Length`, kontrol cache) di satu tempat.
 *  - Membersihkan nama file yang diberikan (hanya `[A-Za-z0-9_-]` yang diizinkan).
 *  - Membangun writer OpenSpout secara lazy (ditunda) sehingga pemanggil dapat dengan mudah
 *    memanggil `addRow()` / `addRows()` kemudian memanggil `download()` atau `toString()`.
 *
 * Penggunaan:
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
     * Menetapkan judul yang mudah dibaca manusia untuk nama tab worksheet.
     *
     * @param  string  $title  String judul (akan di-HTML-escape oleh OpenSpout).
     * @return self Memungkinkan fluent chaining.
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Menambahkan satu baris ke workbook.
     *
     * @param  array<int, string|int|float|null>  $row  Nilai sel untuk baris baru.
     * @return self Memungkinkan fluent chaining.
     */
    public function addRow(array $row): self
    {
        $this->rows[] = array_values($row);

        return $this;
    }

    /**
     * Menambahkan banyak baris sekaligus dalam satu panggilan.
     *
     * @param  iterable<int, array<int, string|int|float|null>>  $rows  Iterable dari array baris data.
     * @return self Memungkinkan fluent chaining.
     */
    public function addRows(iterable $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * Mengalirkan (stream) workbook sebagai unduhan HTTP dan mengakhiri eksekusi skrip.
     *
     * Nama file dibersihkan menjadi `[A-Za-z0-9_-]`; ekstensi `.xlsx` akan
     * ditambahkan secara otomatis. Mengirim header standar `Content-Type` dan
     * `Content-Disposition` yang diharapkan oleh browser.
     *
     * @param  string  $filename  Nama file dasar (tanpa ekstensi).
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
     * Membangun workbook di memori dan mengembalikan payload biner XLSX.
     *
     * @return string Byte mentah `.xlsx` yang siap ditulis ke file atau dialirkan (streamed).
     *
     * @throws \RuntimeException Ketika writer OpenSpout gagal membuka file sementara.
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
