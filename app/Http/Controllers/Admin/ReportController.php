<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Support\XlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display the report-builder landing page with the available kelas and mata pelajaran.
     *
     * @param  Request  $request  Current HTTP request (currently unused).
     * @return InertiaResponse Inertia response rendering `admin/reports/index`.
     */
    public function index(Request $request): InertiaResponse
    {
        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/reports/index', [
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    /**
     * Render the preview page (HTML) for the report, applying the same filter
     * and data-builder pipeline as the export endpoints.
     *
     * @param  Request  $request  Current HTTP request; reads `kelas` (required array) and `mata_pelajaran` (optional array) query parameters.
     * @return InertiaResponse Inertia response rendering `admin/reports/preview` with the aggregated report data.
     */
    public function preview(Request $request): InertiaResponse
    {
        $payload = $this->validateFilter($request);

        $data = $this->buildReportData($payload);

        return Inertia::render('admin/reports/preview', $data);
    }

    /**
     * Stream the report as an A4-landscape PDF file.
     *
     * Uses `barryvdh/laravel-dompdf` to render the `reports.pdf` Blade view.
     *
     * @param  Request  $request  Current HTTP request; reads the same filter parameters as `preview()`.
     * @return Response A download response with `Content-Type: application/pdf`.
     */
    public function exportPdf(Request $request): Response
    {
        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'pdf');

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * Stream the report as a standalone, download-attached HTML file.
     *
     * @param  Request  $request  Current HTTP request; reads the same filter parameters as `preview()`.
     * @return Response A download response with `Content-Type: text/html; charset=utf-8`.
     */
    public function exportHtml(Request $request): Response
    {
        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'html');

        $html = view('reports.html', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.html"',
        ]);
    }

    /**
     * Stream the report as a CSV file. Uses a `StreamedResponse` so the
     * payload is materialised into memory only as a 2-D array; a UTF-8 BOM
     * is prepended so Excel opens the file with the correct encoding.
     *
     * @param  Request  $request  Current HTTP request; reads the same filter parameters as `preview()`.
     * @return StreamedResponse A streamed download response with `Content-Type: text/csv; charset=utf-8`.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'csv');
        $rows = $this->flattenRowsForExport($data);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename.'.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    /**
     * Stream the report as an XLSX (Office Open XML SpreadsheetML) file.
     *
     * Delegates the actual workbook assembly to the `XlsxWriter` support class.
     *
     * @param  Request  $request  Current HTTP request; reads the same filter parameters as `preview()`.
     * @return Response A download response with `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.
     */
    public function exportXlsx(Request $request): Response
    {
        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'xlsx');
        $rows = $this->flattenRowsForExport($data);

        $writer = (new XlsxWriter)
            ->setTitle('Laporan '.implode('_', $payload['kelas']))
            ->addRows($rows);

        $binary = $writer->toString();

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    /**
     * Validate and normalize the multi-kelas + multi-mapel filter.
     *
     * Reads `kelas` (required, at least one) and `mata_pelajaran` (optional)
     * from the request, verifies that every entry exists in the corresponding
     * master table, deduplicates, and sorts the resulting lists for a stable
     * output filename.
     *
     * @param  Request  $request  Current HTTP request carrying the filter parameters.
     * @return array{kelas: array<int, string>, mata_pelajaran: array<int, string>} The normalized filter payload.
     */
    private function validateFilter(Request $request): array
    {
        $validKelas = Kelas::pluck('nama')->all();
        $validMapel = MataPelajaran::pluck('nama')->all();

        $validated = $request->validate([
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*' => ['string', Rule::in($validKelas)],
            'mata_pelajaran' => ['nullable', 'array'],
            'mata_pelajaran.*' => ['string', Rule::in($validMapel)],
        ]);

        $kelas = array_values(array_unique(array_map('strval', $validated['kelas'])));
        sort($kelas);

        $mapel = [];
        if (! empty($validated['mata_pelajaran'])) {
            $mapel = array_values(array_unique(array_map('strval', $validated['mata_pelajaran'])));
            sort($mapel);
        }

        return [
            'kelas' => $kelas,
            'mata_pelajaran' => $mapel,
        ];
    }

    /**
     * Build the human-readable output filename for a given export.
     *
     * Single-kelas payloads use the sanitized class name; multi-kelas payloads
     * collapse to `multi_<n>kelas` to keep the filename short.
     *
     * @param  array{kelas: array<int, string>, mata_pelajaran: array<int, string>}  $payload  The normalized filter payload.
     * @param  string  $ext  Extension suffix (currently unused in the produced filename but kept for future use).
     * @return string The generated filename (without extension).
     */
    private function filenameFor(array $payload, string $ext): string
    {
        $kelas = count($payload['kelas']) === 1
            ? str_replace('-', '_', $payload['kelas'][0])
            : 'multi_'.count($payload['kelas']).'kelas';

        return 'laporan_'.$kelas.'_'.date('Y');
    }

    /**
     * Build the aggregated report data used by every export and the HTML preview.
     *
     * Pipeline:
     *  1. Load all `Siswa` rows whose `kelas` is in the filter.
     *  2. Load all `Nilai` rows for those siswa, optionally restricted to the
     *     selected `mata_pelajaran` filter, grouped by `[nis][mata_pelajaran]`.
     *  3. Group siswa by `kelas` and, for every siswa, accumulate the per-mapel
     *     nilai, the per-kelas pass/fail counts, and the global totals.
     *
     * @param  array{kelas: array<int, string>, mata_pelajaran: array<int, string>}  $payload  The normalized filter payload.
     * @return array{
     *     kelas_list: array<int, string>,
     *     mapel_list: array<int, string>,
     *     sections: array<int, array{
     *         kelas: string,
     *         rows: array<int, array{siswa: Siswa, nilai_per_mapel: array<string, Nilai|null>, rata_rata: float|null}>,
     *         stats: array{jumlah_siswa: int, jumlah_lulus: int, jumlah_tidak_lulus: int}
     *     }>,
     *     stats: array{jumlah_siswa: int, jumlah_lulus: int, jumlah_tidak_lulus: int},
     *     tanggal_cetak: string
     * }  The fully aggregated report payload.
     */
    private function buildReportData(array $payload): array
    {
        $kelasList = $payload['kelas'];
        $mapelFilter = $payload['mata_pelajaran'];

        $siswaList = Siswa::whereIn('kelas', $kelasList)
            ->orderBy('kelas')
            ->orderBy('nis')
            ->get();

        $nilaiQuery = Nilai::whereIn('nis', $siswaList->pluck('nis'))
            ->orderBy('mata_pelajaran');

        if (! empty($mapelFilter)) {
            $nilaiQuery->whereIn('mata_pelajaran', $mapelFilter);
        }

        $nilai = $nilaiQuery->get()->groupBy(['nis', 'mata_pelajaran']);

        $mapelList = $nilaiQuery->clone()->distinct()
            ->orderBy('mata_pelajaran')
            ->pluck('mata_pelajaran')
            ->values()
            ->all();

        $sections = $siswaList->groupBy('kelas')
            ->sortKeys()
            ->map(function ($siswaInKelas, $kelas) use ($nilai, $mapelList) {
                $rows = $siswaInKelas->map(function ($siswa) use ($nilai, $mapelList) {
                    $rowNilai = [];
                    $totalAkhir = 0;
                    $jumlahMapel = 0;

                    foreach ($mapelList as $mapel) {
                        $item = $nilai->get($siswa->nis)?->get($mapel)?->first();
                        $rowNilai[$mapel] = $item;
                        if ($item && $item->nilai_akhir !== null) {
                            $totalAkhir += (float) $item->nilai_akhir;
                            $jumlahMapel++;
                        }
                    }

                    $rataRata = $jumlahMapel > 0 ? round($totalAkhir / $jumlahMapel, 2) : null;

                    return [
                        'siswa' => $siswa,
                        'nilai_per_mapel' => $rowNilai,
                        'rata_rata' => $rataRata,
                    ];
                })->values();

                $lulus = 0;
                $tidakLulus = 0;
                foreach ($rows as $r) {
                    foreach ($r['nilai_per_mapel'] as $n) {
                        if (! $n) {
                            continue;
                        }
                        if ($n->status_lulus === Nilai::LULUS) {
                            $lulus++;
                        } elseif ($n->status_lulus === Nilai::TIDAK_LULUS) {
                            $tidakLulus++;
                        }
                    }
                }

                return [
                    'kelas' => $kelas,
                    'rows' => $rows,
                    'stats' => [
                        'jumlah_siswa' => $siswaInKelas->count(),
                        'jumlah_lulus' => $lulus,
                        'jumlah_tidak_lulus' => $tidakLulus,
                    ],
                ];
            })
            ->values()
            ->all();

        $totalSiswa = 0;
        $totalLulus = 0;
        $totalTidakLulus = 0;
        foreach ($sections as $s) {
            $totalSiswa += $s['stats']['jumlah_siswa'];
            $totalLulus += $s['stats']['jumlah_lulus'];
            $totalTidakLulus += $s['stats']['jumlah_tidak_lulus'];
        }

        return [
            'kelas_list' => $kelasList,
            'mapel_list' => $mapelList,
            'sections' => $sections,
            'stats' => [
                'jumlah_siswa' => $totalSiswa,
                'jumlah_lulus' => $totalLulus,
                'jumlah_tidak_lulus' => $totalTidakLulus,
            ],
            'tanggal_cetak' => now()->format('d F Y'),
        ];
    }

    /**
     * Flatten the section-based report data into a 2-D array suitable for
     * CSV and XLSX export. The first row is the header; subsequent rows
     * contain one siswa per row with columns for `Kelas`, `NIS`, `Nama Siswa`,
     * followed by `Tgs / UTS / UAS / Akhir` groups for every `mata_pelajaran`,
     * and a final `Rata-rata` column.
     *
     * @param  array{
     *     kelas_list: array<int, string>,
     *     mapel_list: array<int, string>,
     *     sections: array<int, array{
     *         kelas: string,
     *         rows: array<int, array{siswa: Siswa, nilai_per_mapel: array<string, Nilai|null>, rata_rata: float|null}>,
     *         stats: array{jumlah_siswa: int, jumlah_lulus: int, jumlah_tidak_lulus: int}
     *     }>,
     *     stats: array{jumlah_siswa: int, jumlah_lulus: int, jumlah_tidak_lulus: int},
     *     tanggal_cetak: string
     * }  $data  The aggregated report data produced by `buildReportData()`.
     * @return array<int, array<int, string|int|float|null>> A 2-D array of header + data rows.
     */
    private function flattenRowsForExport(array $data): array
    {
        $header = ['Kelas', 'NIS', 'Nama Siswa'];
        foreach ($data['mapel_list'] as $m) {
            $header[] = $m.' (Tgs)';
            $header[] = $m.' (UTS)';
            $header[] = $m.' (UAS)';
            $header[] = $m.' (Akhir)';
        }
        $header[] = 'Rata-rata';

        $rows = [$header];
        foreach ($data['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                $flat = [
                    $row['siswa']->kelas,
                    $row['siswa']->nis,
                    $row['siswa']->nama_siswa,
                ];
                foreach ($data['mapel_list'] as $m) {
                    $n = $row['nilai_per_mapel'][$m] ?? null;
                    $flat[] = $n?->nilai_tugas;
                    $flat[] = $n?->nilai_uts;
                    $flat[] = $n?->nilai_uas;
                    $flat[] = $n?->nilai_akhir;
                }
                $flat[] = $row['rata_rata'];
                $rows[] = $flat;
            }
        }

        return $rows;
    }
}
