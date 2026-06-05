<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $daftarKelas = Siswa::query()->distinct()->orderBy('kelas')->pluck('kelas');

        return Inertia::render('admin/reports/index', [
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    public function preview(Request $request): InertiaResponse
    {
        $request->validate([
            'kelas' => ['required', 'string'],
        ]);

        $data = $this->buildReportData($request->input('kelas'));

        return Inertia::render('admin/reports/preview', $data);
    }

    public function exportPdf(Request $request): Response
    {
        $request->validate(['kelas' => ['required', 'string']]);

        $data = $this->buildReportData($request->input('kelas'));
        $kelas = $request->input('kelas');

        $pdf = Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'laporan_kelas_'.str_replace('-', '_', $kelas).'_'.date('Y').'.pdf';

        return $pdf->download($filename);
    }

    public function exportHtml(Request $request): Response
    {
        $request->validate(['kelas' => ['required', 'string']]);

        $data = $this->buildReportData($request->input('kelas'));
        $kelas = $request->input('kelas');

        $html = view('reports.html', $data)->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="laporan_kelas_'.str_replace('-', '_', $kelas).'_'.date('Y').'.html"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(string $kelas): array
    {
        $siswaList = Siswa::where('kelas', $kelas)
            ->orderBy('nis')
            ->get();

        $nisSiswa = $siswaList->pluck('nis');

        $nilai = Nilai::whereIn('nis', $nisSiswa)
            ->orderBy('mata_pelajaran')
            ->get()
            ->groupBy(['nis', 'mata_pelajaran']);

        $mapelList = Nilai::whereIn('nis', $nisSiswa)
            ->distinct()
            ->orderBy('mata_pelajaran')
            ->pluck('mata_pelajaran');

        $rows = $siswaList->map(function ($siswa) use ($nilai, $mapelList) {
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
        });

        $jumlahLulus = 0;
        $jumlahTidakLulus = 0;
        foreach ($rows as $r) {
            foreach ($r['nilai_per_mapel'] as $n) {
                if ($n) {
                    if ($n->status_lulus === Nilai::LULUS) {
                        $jumlahLulus++;
                    } elseif ($n->status_lulus === Nilai::TIDAK_LULUS) {
                        $jumlahTidakLulus++;
                    }
                }
            }
        }

        return [
            'kelas' => $kelas,
            'rows' => $rows,
            'mapel_list' => $mapelList,
            'stats' => [
                'jumlah_siswa' => $siswaList->count(),
                'jumlah_lulus' => $jumlahLulus,
                'jumlah_tidak_lulus' => $jumlahTidakLulus,
            ],
            'tanggal_cetak' => now()->format('d F Y'),
        ];
    }
}
