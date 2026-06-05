<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $rekapPerKelas = $this->buildRekapPerKelas();
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

    /**
     * @return array<int, array{kelas: string, jumlah_siswa: int, lulus: int, tidak_lulus: int}>
     */
    private function buildRekapPerKelas(): array
    {
        $siswaPerKelas = Siswa::query()
            ->select('kelas')
            ->selectRaw('COUNT(*) as jumlah_siswa')
            ->groupBy('kelas')
            ->pluck('jumlah_siswa', 'kelas');

        $nilaiPerKelas = DB::table('nilai')
            ->join('siswa', 'siswa.nis', '=', 'nilai.nis')
            ->groupBy('siswa.kelas', 'nilai.status_lulus')
            ->select([
                'siswa.kelas',
                'nilai.status_lulus',
                DB::raw('COUNT(*) as total'),
            ])
            ->get()
            ->groupBy('kelas');

        $rekap = [];
        foreach ($siswaPerKelas->keys()->sort() as $kelas) {
            $statusCounts = $nilaiPerKelas->get($kelas, collect());
            $rekap[] = [
                'kelas' => $kelas,
                'jumlah_siswa' => (int) $siswaPerKelas[$kelas],
                'lulus' => (int) $statusCounts->firstWhere('status_lulus', Nilai::LULUS)?->total ?? 0,
                'tidak_lulus' => (int) $statusCounts->firstWhere('status_lulus', Nilai::TIDAK_LULUS)?->total ?? 0,
            ];
        }

        return $rekap;
    }
}
