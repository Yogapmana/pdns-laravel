<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalSiswa = Siswa::count();
        $totalGuru = Guru::count();
        $totalNilai = Nilai::count();

        $lulus = Nilai::where('status_lulus', Nilai::LULUS)->count();
        $tidakLulus = Nilai::where('status_lulus', Nilai::TIDAK_LULUS)->count();
        $persentaseLulus = $totalNilai > 0 ? round(($lulus / $totalNilai) * 100, 1) : 0;

        $rekapPerKelas = Siswa::query()
            ->select('kelas')
            ->selectRaw('COUNT(DISTINCT siswa.nis) as jumlah_siswa')
            ->groupBy('kelas')
            ->orderBy('kelas')
            ->get()
            ->map(function ($row) {
                $nisSiswaKelas = Siswa::where('kelas', $row->kelas)->pluck('nis');

                $lulusKelas = Nilai::whereIn('nis', $nisSiswaKelas)
                    ->where('status_lulus', Nilai::LULUS)
                    ->count();
                $tidakLulusKelas = Nilai::whereIn('nis', $nisSiswaKelas)
                    ->where('status_lulus', Nilai::TIDAK_LULUS)
                    ->count();

                return [
                    'kelas' => $row->kelas,
                    'jumlah_siswa' => (int) $row->jumlah_siswa,
                    'lulus' => $lulusKelas,
                    'tidak_lulus' => $tidakLulusKelas,
                ];
            });

        $daftarKelas = Siswa::query()->distinct()->orderBy('kelas')->pluck('kelas');

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'total_siswa' => $totalSiswa,
                'total_guru' => $totalGuru,
                'total_nilai' => $totalNilai,
                'lulus' => $lulus,
                'tidak_lulus' => $tidakLulus,
                'persentase_lulus' => $persentaseLulus,
            ],
            'rekap_per_kelas' => $rekapPerKelas,
            'daftar_kelas' => $daftarKelas,
        ]);
    }
}
