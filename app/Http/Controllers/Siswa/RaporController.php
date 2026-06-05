<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class RaporController extends Controller
{
    public function pdf(): Response
    {
        $siswa = Siswa::where('user_id', auth()->id())->firstOrFail();

        $nilai = Nilai::with('guru:id,nama_guru')
            ->where('nis', $siswa->nis)
            ->orderBy('mata_pelajaran')
            ->get();

        $guruMap = Guru::whereIn('id', $nilai->pluck('id_guru')->unique())
            ->get()
            ->keyBy('id');

        $perMapel = $nilai
            ->groupBy('mata_pelajaran')
            ->map(function ($items, $mapel) {
                $item = $items->first();
                $nilaiAkhir = $item->nilai_akhir !== null ? (float) $item->nilai_akhir : null;
                $tugas = $item->nilai_tugas !== null ? (float) $item->nilai_tugas : null;
                $uts = $item->nilai_uts !== null ? (float) $item->nilai_uts : null;
                $uas = $item->nilai_uas !== null ? (float) $item->nilai_uas : null;

                return [
                    'mapel' => $mapel,
                    'kelas' => $item->kelas,
                    'nama_guru' => $item->guru?->nama_guru ?? '—',
                    'tugas' => $tugas,
                    'uts' => $uts,
                    'uas' => $uas,
                    'akhir' => $nilaiAkhir,
                    'status' => $item->status_lulus,
                    'status_validasi' => $item->status_validasi,
                ];
            })
            ->values()
            ->all();

        $nilaiAkhirValues = array_filter(array_column($perMapel, 'akhir'), fn ($v) => $v !== null);
        $jumlahMapel = count($perMapel);
        $lulus = collect($perMapel)->where('status', Nilai::LULUS)->count();
        $tidakLulus = collect($perMapel)->where('status', Nilai::TIDAK_LULUS)->count();
        $rataRata = count($nilaiAkhirValues) > 0
            ? round(array_sum($nilaiAkhirValues) / count($nilaiAkhirValues), 2)
            : null;

        $tahunAjaran = $this->guessTahunAjaran();

        $data = [
            'siswa' => $siswa,
            'per_mapel' => $perMapel,
            'jumlah_mapel' => $jumlahMapel,
            'lulus' => $lulus,
            'tidak_lulus' => $tidakLulus,
            'rata_rata' => $rataRata,
            'kkm' => Nilai::KKM,
            'tanggal_cetak' => now()->translatedFormat('d F Y'),
            'tahun_ajaran' => $tahunAjaran,
        ];

        $pdf = Pdf::loadView('reports.rapor-pdf', $data)->setPaper('a4', 'portrait');

        $filename = 'Rapor_'.str_replace(' ', '_', $siswa->nama_siswa).'_'.$siswa->nis.'.pdf';

        return $pdf->download($filename);
    }

    private function guessTahunAjaran(): string
    {
        $now = now();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        if ($month >= 7) {
            return $year.'/'.($year + 1);
        }

        return ($year - 1).'/'.$year;
    }
}
