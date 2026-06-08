<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuruMengajar;
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
     * Menampilkan halaman utama pembuat laporan dengan kelas dan mata pelajaran yang tersedia.
     *
     * @param  Request  $request  Request HTTP saat ini.
     * @return InertiaResponse Respon Inertia yang merender view `admin/reports/index`.
     */
    public function index(Request $request): InertiaResponse
    {
        $daftarKelas = Kelas::pluckIdNamaOrdered();
        $daftarMapel = MataPelajaran::pluckIdNamaOrdered();

        return Inertia::render('admin/reports/index', [
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    /**
     * Merender halaman pratinjau (HTML) untuk laporan, menerapkan filter yang sama
     * dan alur pembangunan data seperti endpoint ekspor.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter kueri `kelas` (wajib berupa array) dan `mata_pelajaran` (opsional berupa array).
     * @return InertiaResponse Respon Inertia yang merender view `admin/reports/preview` dengan data laporan teragregasi.
     */
    public function preview(Request $request): InertiaResponse
    {
        $payload = $this->validateFilter($request);

        $data = $this->buildReportData($payload);

        return Inertia::render('admin/reports/preview', $data);
    }

    /**
     * Mengalirkan (stream) laporan sebagai file PDF lanskap berukuran A4.
     *
     * Menggunakan `barryvdh/laravel-dompdf` untuk merender view Blade `reports.pdf`.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter filter yang sama dengan `preview()`.
     * @return Response Respon unduhan dengan header `Content-Type: application/pdf`.
     */
    public function exportPdf(Request $request): Response
    {
        // Increase limits for DOMPDF when generating large datasets (e.g., all classes and subjects)
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');

        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'pdf');

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * Mengalirkan (stream) laporan sebagai file HTML mandiri yang dilampirkan untuk diunduh.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter filter yang sama dengan `preview()`.
     * @return Response Respon unduhan dengan header `Content-Type: text/html; charset=utf-8`.
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
     * Mengalirkan (stream) laporan sebagai file CSV. Menggunakan `StreamedResponse` sehingga data
     * hanya dimuat ke memori sebagai array 2 dimensi; BOM UTF-8 ditambahkan di awal
     * agar Excel membuka file dengan pengodean (encoding) yang benar.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter filter yang sama dengan `preview()`.
     * @return StreamedResponse Respon unduhan ter-stream dengan header `Content-Type: text/csv; charset=utf-8`.
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
     * Mengalirkan (stream) laporan sebagai file XLSX (Office Open XML SpreadsheetML).
     *
     * Mendelegasikan perakitan workbook yang sebenarnya ke kelas pembantu `XlsxWriter`.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter filter yang sama dengan `preview()`.
     * @return Response Respon unduhan dengan header `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.
     */
    public function exportXlsx(Request $request): Response
    {
        $payload = $this->validateFilter($request);
        $data = $this->buildReportData($payload);
        $filename = $this->filenameFor($payload, 'xlsx');
        $rows = $this->flattenRowsForExport($data);

        $writer = (new XlsxWriter)
            ->setTitle('Laporan '.implode('_', $payload['kelas_names']))
            ->addRows($rows);

        $binary = $writer->toString();

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    /**
     * Memvalidasi dan menormalisasi filter multi-kelas + multi-mapel.
     *
     * Membaca `kelas` (wajib, minimal satu) dan `mata_pelajaran` (opsional) dari request,
     * memastikan bahwa setiap entri ada di tabel master yang sesuai, mendeduplikasi,
     * dan mengurutkan daftar hasilnya untuk nama file output yang stabil. Payload yang dikembalikan
     * menggunakan `kelas_id`/`mata_pelajaran_id` secara internal; nama yang mudah dibaca manusia
     * dipertahankan pada kunci `kelas_names` / `mapel_names` untuk nama file dan tampilan.
     *
     * @param  Request  $request  Request HTTP saat ini yang membawa parameter filter.
     * @return array{
     *     kelas: array<int, int>,
     *     kelas_names: array<int, string>,
     *     mata_pelajaran: array<int, int>,
     *     mapel_names: array<int, string>
     * } Payload filter yang telah dinormalisasi.
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
            'sort' => ['nullable', 'string', Rule::in(['abjad', 'ranking', 'perhatian'])],
            'sort_type' => ['nullable', 'string', Rule::in(['per_kelas', 'paralel'])],
        ]);

        $kelasNames = array_values(array_unique(array_map('strval', $validated['kelas'])));
        sort($kelasNames);

        $kelasIds = Kelas::whereIn('nama', $kelasNames)->orderBy('nama')->pluck('id')->all();

        $mapelNames = [];
        $mapelIds = [];
        if (! empty($validated['mata_pelajaran'])) {
            $mapelNames = array_values(array_unique(array_map('strval', $validated['mata_pelajaran'])));
            sort($mapelNames);
            $mapelIds = MataPelajaran::whereIn('nama', $mapelNames)->orderBy('nama')->pluck('id')->all();
        }

        return [
            'kelas' => $kelasIds,
            'kelas_names' => $kelasNames,
            'mata_pelajaran' => $mapelIds,
            'mapel_names' => $mapelNames,
            'sort' => $validated['sort'] ?? 'abjad',
            'sort_type' => $validated['sort_type'] ?? 'per_kelas',
        ];
    }

    /**
     * Membangun nama file output yang mudah dibaca manusia untuk ekspor tertentu.
     *
     * Payload kelas tunggal menggunakan nama kelas yang telah dibersihkan; payload multi-kelas
     * diringkas menjadi `multi_<n>kelas` agar nama file tetap pendek.
     *
     * @param  array{
     *     kelas: array<int, int>,
     *     kelas_names: array<int, string>,
     *     mata_pelajaran: array<int, int>,
     *     mapel_names: array<int, string>
     * }  $payload  Payload filter yang telah dinormalisasi.
     * @param  string  $ext  Sufiks ekstensi (saat ini tidak digunakan dalam pembuatan nama file tetapi dipertahankan untuk masa depan).
     * @return string Nama file yang dihasilkan (tanpa ekstensi).
     */
    private function filenameFor(array $payload, string $ext): string
    {
        $kelas = count($payload['kelas_names']) === 1
            ? str_replace('-', '_', $payload['kelas_names'][0])
            : 'multi_'.count($payload['kelas_names']).'kelas';

        return 'laporan_'.$kelas.'_'.date('Y');
    }

    /**
     * Membangun data laporan teragregasi yang digunakan oleh setiap ekspor dan pratinjau HTML.
     *
     * Alur (Pipeline):
     *  1. Memuat semua baris `Siswa` yang `kelas_id` nya ada dalam filter (dengan kelas di-eager-load untuk tampilan).
     *  2. Memuat semua baris `Nilai` untuk siswa-siswa tersebut, secara opsional dibatasi pada filter
     *     `mata_pelajaran_id` yang dipilih, dikelompokkan berdasarkan `[nis][mata_pelajaran_id]`.
     *  3. Mengelompokkan siswa berdasarkan `kelas.nama` dan, untuk setiap siswa, mengakumulasikan
     *     nilai per mapel, jumlah lulus/tidak lulus per kelas, dan total global.
     *
     * @param  array{
     *     kelas: array<int, int>,
     *     kelas_names: array<int, string>,
     *     mata_pelajaran: array<int, int>,
     *     mapel_names: array<int, string>
     * }  $payload  Payload filter yang telah dinormalisasi.
     * @return array{
     *     kelas_list: array<int, string>,
     *     mapel_list: array<int, string>,
     *     sections: array<int, array{
     *         kelas: string,
     *         rows: array<int, array{siswa: Siswa, nilai_per_mapel: array<int, Nilai|null>, rata_rata: float|null}>,
     *         stats: array{jumlah_siswa: int, jumlah_lulus: int, jumlah_tidak_lulus: int}
     *     }>,
     *     stats: array{jumlah_siswa: int, jumlah_lulus: int, jumlah_tidak_lulus: int},
     *     tanggal_cetak: string
     * } Payload laporan yang telah teragregasi sepenuhnya.
     */
    private function buildReportData(array $payload): array
    {
        $kelasIds = $payload['kelas'];
        $kelasNames = $payload['kelas_names'];
        $mapelIds = $payload['mata_pelajaran'];
        $sort = $payload['sort'];
        $sortType = $payload['sort_type'];

        $siswaList = Siswa::with('kelas:id,nama')
            ->whereIn('kelas_id', $kelasIds)
            ->orderBy('kelas_id')
            ->orderBy('nis')
            ->get();

        $nilaiQuery = Nilai::with('mataPelajaran:id,nama')
            ->whereIn('nis', $siswaList->pluck('nis'));

        if (! empty($mapelIds)) {
            $nilaiQuery->whereIn('mata_pelajaran_id', $mapelIds);
        }

        $nilai = $nilaiQuery->get()->groupBy(['nis', 'mata_pelajaran_id']);

        $mapelList = empty($mapelIds)
            ? MataPelajaran::whereIn('id', $nilaiQuery->clone()->distinct()->pluck('mata_pelajaran_id'))->orderBy('nama')->pluck('nama')->all()
            : $payload['mapel_names'];

        $sections = $siswaList->groupBy(fn ($s) => $sortType === 'paralel' ? 'Semua Kelas (Paralel)' : ($s->kelas?->nama ?? '?'))
            ->sortKeys()
            ->map(function ($siswaInKelas, $kelasNama) use ($nilai, $payload, $sort, $sortType) {
                $rows = $siswaInKelas->map(function ($siswa) use ($nilai) {
                    $rowNilai = [];
                    $totalAkhir = 0;
                    $jumlahMapel = 0;

                    foreach ($nilai->get($siswa->nis, collect()) as $mapelId => $items) {
                        $item = $items->first();
                        $mapelName = $item?->mataPelajaran?->nama ?? $mapelId;
                        $rowNilai[$mapelName] = $item;
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
                });

                if ($sort === 'ranking') {
                    $rows = $rows->sortByDesc('rata_rata')->values();
                } elseif ($sort === 'perhatian') {
                    $rows = $rows->sortBy(fn ($r) => $r['rata_rata'] ?? 999)->values();
                } else {
                    $rows = $rows->sortBy(fn ($r) => $r['siswa']->nama_siswa)->values();
                }

                if ($sortType === 'paralel') {
                    $mapelDiKelas = GuruMengajar::whereIn('kelas_id', $payload['kelas'])
                        ->with('mataPelajaran:id,nama')
                        ->get()
                        ->pluck('mataPelajaran.nama')
                        ->unique()
                        ->all();
                } else {
                    $kelasId = $siswaInKelas->first()->kelas_id;
                    $mapelDiKelas = GuruMengajar::where('kelas_id', $kelasId)
                        ->with('mataPelajaran:id,nama')
                        ->get()
                        ->pluck('mataPelajaran.nama')
                        ->unique()
                        ->all();
                }

                $sectionMapelNames = array_flip($mapelDiKelas);

                $lulus = 0;
                $tidakLulus = 0;
                foreach ($rows as $r) {
                    foreach (array_keys($r['nilai_per_mapel']) as $m) {
                        $sectionMapelNames[$m] = true;
                    }
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

                $sectionMapelList = array_keys($sectionMapelNames);
                sort($sectionMapelList);
                if (! empty($payload['mapel_names'])) {
                    $sectionMapelList = array_values(array_intersect($sectionMapelList, $payload['mapel_names']));
                }

                return [
                    'kelas' => $kelasNama,
                    'mapel_list' => $sectionMapelList,
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
            'kelas_list' => $kelasNames,
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
     * Meratakan (flatten) data laporan berbasis seksi menjadi array 2 dimensi yang cocok untuk
     * ekspor CSV dan XLSX. Baris pertama adalah header; baris berikutnya berisi satu siswa
     * per baris dengan kolom `Kelas`, `NIS`, `Nama Siswa`, diikuti oleh grup `Tgs / UTS / UAS / Akhir`
     * untuk setiap `mata_pelajaran`, dan kolom akhir `Rata-rata`.
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
     * }  $data  Data laporan teragregasi yang dihasilkan oleh `buildReportData()`.
     * @return array<int, array<int, string|int|float|null>> Array 2 dimensi berisi header + baris data.
     */
    private function flattenRowsForExport(array $data): array
    {
        $rows = [];
        foreach ($data['sections'] as $section) {
            // Write a header row for each section because columns vary by class
            $header = ['Kelas', 'NIS', 'Nama Siswa'];
            foreach ($section['mapel_list'] as $m) {
                $header[] = $m.' (Tgs)';
                $header[] = $m.' (UTS)';
                $header[] = $m.' (UAS)';
                $header[] = $m.' (Akhir)';
            }
            $header[] = 'Rata-rata';
            $rows[] = $header;

            foreach ($section['rows'] as $row) {
                $flat = [
                    $section['kelas'],
                    $row['siswa']->nis,
                    $row['siswa']->nama_siswa,
                ];
                foreach ($section['mapel_list'] as $m) {
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
