<?php

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
    public function index(Request $request): InertiaResponse
    {
        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/reports/index', [
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    public function preview(Request $request): InertiaResponse
    {
        $payload = $this->validateFilter($request);

        $data = $this->buildReportData($payload);

        return Inertia::render('admin/reports/preview', $data);
    }

    public function exportPdf(Request $request): Response
    {
        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'pdf');

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

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
     * Validate + normalize multi-kelas + multi-mapel filter.
     *
     * @return array{kelas: array<int, string>, mata_pelajaran: array<int, string>}
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
     * @param  array{kelas: array<int, string>, mata_pelajaran: array<int, string>}  $payload
     */
    private function filenameFor(array $payload, string $ext): string
    {
        $kelas = count($payload['kelas']) === 1
            ? str_replace('-', '_', $payload['kelas'][0])
            : 'multi_'.count($payload['kelas']).'kelas';

        return 'laporan_'.$kelas.'_'.date('Y');
    }

    /**
     * Build the report data: one or more "kelas" sections, each with rows of siswa.
     *
     * @param  array{kelas: array<int, string>, mata_pelajaran: array<int, string>}  $payload
     * @return array<string, mixed>
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
     * @return array<int, array<int, string|int|float|null>>
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
