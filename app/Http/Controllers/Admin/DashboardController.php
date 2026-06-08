<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with aggregated statistics.
     *
     * Renders the global counters (total siswa, guru, nilai, mata pelajaran),
     * the per-kelas rekap, per-mapel averages, top 5 siswa by average score,
     * and the 5 siswa with the highest "perhatian" ratio (most "Tidak Lulus"
     * values). Uses eager loading and grouped queries to keep database
     * round-trips at a constant count regardless of dataset size.
     *
     * @param  Request  $request  The current HTTP request (currently unused beyond type-hint).
     * @return Response Inertia response rendering the `admin/dashboard` page.
     */
    public function index(Request $request): Response
    {
        $totalSiswa = Siswa::count();
        $totalGuru = Guru::count();
        $totalNilai = Nilai::count();
        $totalMapel = MataPelajaran::count();

        $lulus = Nilai::where('status_lulus', Nilai::LULUS)->count();
        $tidakLulus = Nilai::where('status_lulus', Nilai::TIDAK_LULUS)->count();
        $persentaseLulus = $totalNilai > 0 ? round(($lulus / $totalNilai) * 100, 1) : 0;

        $rekapPerKelas = $this->buildRekapPerKelas();
        $rataRataPerMapel = $this->buildRataRataPerMapel();
        $topSiswa = $this->buildTopSiswa();
        $siswaPerhatian = $this->buildSiswaPerhatian();
        $daftarKelas = Kelas::pluckNamaOrdered();
        $kkm = Nilai::KKM;

        $tindakanPenting = [];

        $siswaTanpaNilai = Siswa::doesntHave('nilai')->count();
        if ($siswaTanpaNilai > 0) {
            $tindakanPenting[] = [
                'id' => 'siswa-no-nilai',
                'title' => 'Siswa Tanpa Nilai',
                'description' => "{$siswaTanpaNilai} siswa belum memiliki rekam nilai sama sekali.",
                'priority' => 'high',
                'href' => '/admin/siswa',
            ];
        }

        $guruTanpaMengajar = Guru::doesntHave('mengajar')->count();
        if ($guruTanpaMengajar > 0) {
            $tindakanPenting[] = [
                'id' => 'guru-no-jadwal',
                'title' => 'Guru Belum Dijadwalkan',
                'description' => "{$guruTanpaMengajar} guru belum ditugaskan mengajar kelas/mapel apa pun.",
                'priority' => 'medium',
                'href' => '/admin/guru',
            ];
        }

        $kelasKritisCount = 0;
        foreach ($rekapPerKelas as $rekap) {
            if ($rekap['persentase_lulus'] < 70) {
                $kelasKritisCount++;
            }
        }
        if ($kelasKritisCount > 0) {
            $tindakanPenting[] = [
                'id' => 'kelas-kritis',
                'title' => 'Kelas Butuh Intervensi',
                'description' => "{$kelasKritisCount} kelas memiliki tingkat kelulusan di bawah 70%.",
                'priority' => 'high',
                'href' => '/admin/siswa',
            ];
        }

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'total_siswa' => $totalSiswa,
                'total_guru' => $totalGuru,
                'total_nilai' => $totalNilai,
                'total_mapel' => $totalMapel,
                'lulus' => $lulus,
                'tidak_lulus' => $tidakLulus,
                'persentase_lulus' => $persentaseLulus,
            ],
            'rekap_per_kelas' => $rekapPerKelas,
            'rata_rata_per_mapel' => $rataRataPerMapel,
            'top_siswa' => $topSiswa,
            'siswa_perhatian' => $siswaPerhatian,
            'daftar_kelas' => $daftarKelas,
            'kkm' => (float) $kkm,
            'tindakan_penting' => $tindakanPenting,
        ]);
    }

    /**
     * Build per-kelas rekapitulasi: jumlah siswa, lulus, tidak lulus, dan persentase kelulusan.
     *
     * Performs a single grouped SELECT against `siswa` for the student counts
     * and a single grouped JOIN against `nilai` for the lulus/tidak_lulus counts,
     * then merges them in PHP. This avoids the N+1 trap that would otherwise
     * occur when iterating over every class.
     *
     * @return array<int, array{
     *     kelas: string,
     *     jumlah_siswa: int,
     *     lulus: int,
     *     tidak_lulus: int,
     *     total_nilai: int,
     *     persentase_lulus: float
     * }>  List of rekap rows, sorted by class name ascending.
     */
    private function buildRekapPerKelas(): array
    {
        $siswaPerKelas = DB::table('siswa')
            ->join('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->groupBy('kelas.nama')
            ->orderBy('kelas.nama')
            ->selectRaw('kelas.nama as kelas, COUNT(*) as jumlah_siswa')
            ->pluck('jumlah_siswa', 'kelas');

        $nilaiPerKelas = DB::table('nilai')
            ->join('siswa', 'siswa.nis', '=', 'nilai.nis')
            ->join('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->groupBy('kelas.nama', 'nilai.status_lulus')
            ->select([
                'kelas.nama as kelas',
                'nilai.status_lulus',
                DB::raw('COUNT(*) as total'),
            ])
            ->get()
            ->groupBy('kelas');

        $rekap = [];
        foreach ($siswaPerKelas->keys()->sort() as $kelas) {
            $statusCounts = $nilaiPerKelas->get($kelas, collect());
            $lulus = (int) $statusCounts->firstWhere('status_lulus', Nilai::LULUS)?->total ?? 0;
            $tidakLulus = (int) $statusCounts->firstWhere('status_lulus', Nilai::TIDAK_LULUS)?->total ?? 0;
            $totalNilai = $lulus + $tidakLulus;
            $persentase = $totalNilai > 0 ? (float) round(($lulus / $totalNilai) * 100, 1) : 0.0;

            $rekap[] = [
                'kelas' => $kelas,
                'jumlah_siswa' => (int) $siswaPerKelas[$kelas],
                'lulus' => $lulus,
                'tidak_lulus' => $tidakLulus,
                'total_nilai' => $totalNilai,
                'persentase_lulus' => $persentase,
            ];
        }

        usort($rekap, function ($a, $b) {
            return $a['persentase_lulus'] <=> $b['persentase_lulus'];
        });

        return $rekap;
    }

    /**
     * Build per-mata-pelajaran averages aggregated from the entire `nilai` table.
     *
     * Uses MySQL `AVG()` and conditional `SUM(CASE WHEN ...)` expressions in a
     * single grouped query to compute the average, the pass/fail totals and
     * the pass-rate percentage per subject. Results are then sorted in PHP
     * by `rata_rata` descending.
     *
     * @return array<int, array{
     *     mata_pelajaran: string,
     *     rata_rata: float,
     *     total_nilai: int,
     *     lulus: int,
     *     tidak_lulus: int,
     *     persentase_lulus: float
     * }>  List of mapel rows, sorted by `rata_rata` descending.
     */
    private function buildRataRataPerMapel(): array
    {
        $lulusValue = Nilai::LULUS;
        $tidakLulusValue = Nilai::TIDAK_LULUS;

        $rows = DB::table('nilai')
            ->join('mata_pelajaran', 'mata_pelajaran.id', '=', 'nilai.mata_pelajaran_id')
            ->groupBy('mata_pelajaran.id', 'mata_pelajaran.nama')
            ->select([
                'mata_pelajaran.nama as mata_pelajaran',
                DB::raw('AVG(nilai.nilai_akhir) as rata_rata'),
                DB::raw('COUNT(*) as total_nilai'),
                DB::raw("SUM(CASE WHEN nilai.status_lulus = '{$lulusValue}' THEN 1 ELSE 0 END) as lulus"),
                DB::raw("SUM(CASE WHEN nilai.status_lulus = '{$tidakLulusValue}' THEN 1 ELSE 0 END) as tidak_lulus"),
            ])
            ->get();

        return $rows
            ->map(function ($r) {
                $lulus = (int) $r->lulus;
                $tidakLulus = (int) $r->tidak_lulus;
                $total = (int) $r->total_nilai;
                $persentase = $total > 0 ? (float) round(($lulus / $total) * 100, 1) : 0.0;

                return [
                    'mata_pelajaran' => $r->mata_pelajaran,
                    'rata_rata' => (float) round((float) $r->rata_rata, 2),
                    'total_nilai' => $total,
                    'lulus' => $lulus,
                    'tidak_lulus' => $tidakLulus,
                    'persentase_lulus' => $persentase,
                ];
            })
            ->sortBy('rata_rata')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Build the top-N siswa leaderboard ordered by average `nilai_akhir` descending.
     *
     * Only siswa with at least one row in the `nilai` table are included
     * (enforced via `HAVING COUNT(*) > 0`).
     *
     * @param  int  $limit  Maximum number of siswa to return. Defaults to 5.
     * @return array<int, array{
     *     nis: string,
     *     nama_siswa: string,
     *     kelas: string,
     *     rata_rata: float,
     *     total_mapel: int,
     *     lulus: int,
     *     tidak_lulus: int
     * }>  Leaderboard rows, sorted by `rata_rata` descending.
     */
    private function buildTopSiswa(int $limit = 5): array
    {
        $lulusValue = Nilai::LULUS;
        $tidakLulusValue = Nilai::TIDAK_LULUS;

        return DB::table('nilai')
            ->join('siswa', 'siswa.nis', '=', 'nilai.nis')
            ->join('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->groupBy('nilai.nis', 'siswa.nama_siswa', 'kelas.nama')
            ->select([
                'nilai.nis',
                'siswa.nama_siswa',
                'kelas.nama as kelas',
                DB::raw('AVG(nilai.nilai_akhir) as rata_rata'),
                DB::raw('COUNT(*) as total_mapel'),
                DB::raw("SUM(CASE WHEN nilai.status_lulus = '{$lulusValue}' THEN 1 ELSE 0 END) as lulus"),
                DB::raw("SUM(CASE WHEN nilai.status_lulus = '{$tidakLulusValue}' THEN 1 ELSE 0 END) as tidak_lulus"),
            ])
            ->havingRaw('COUNT(*) > 0')
            ->orderByDesc('rata_rata')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'nis' => $r->nis,
                'nama_siswa' => $r->nama_siswa,
                'kelas' => $r->kelas,
                'rata_rata' => (float) round((float) $r->rata_rata, 2),
                'total_mapel' => (int) $r->total_mapel,
                'lulus' => (int) $r->lulus,
                'tidak_lulus' => (int) $r->tidak_lulus,
            ])
            ->all();
    }

    /**
     * Build the "siswa perhatian" list — siswa with at least one `Tidak Lulus`
     * subject, ordered by the absolute number of failed subjects descending,
     * and then by average score ascending (so the worst cases float to the top).
     *
     * Includes an additional `rasio_tidak_lulus` percentage for the UI badge.
     *
     * @param  int  $limit  Maximum number of siswa to return. Defaults to 5.
     * @return array<int, array{
     *     nis: string,
     *     nama_siswa: string,
     *     kelas: string,
     *     rata_rata: float,
     *     total_mapel: int,
     *     lulus: int,
     *     tidak_lulus: int,
     *     rasio_tidak_lulus: float
     * }>  Siswa rows, sorted by `tidak_lulus` descending, then `rata_rata` ascending.
     */
    private function buildSiswaPerhatian(int $limit = 5): array
    {
        $lulusValue = Nilai::LULUS;
        $tidakLulusValue = Nilai::TIDAK_LULUS;

        return DB::table('nilai')
            ->join('siswa', 'siswa.nis', '=', 'nilai.nis')
            ->join('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->groupBy('nilai.nis', 'siswa.nama_siswa', 'kelas.nama')
            ->select([
                'nilai.nis',
                'siswa.nama_siswa',
                'kelas.nama as kelas',
                DB::raw('AVG(nilai.nilai_akhir) as rata_rata'),
                DB::raw('COUNT(*) as total_mapel'),
                DB::raw("SUM(CASE WHEN nilai.status_lulus = '{$lulusValue}' THEN 1 ELSE 0 END) as lulus"),
                DB::raw("SUM(CASE WHEN nilai.status_lulus = '{$tidakLulusValue}' THEN 1 ELSE 0 END) as tidak_lulus"),
            ])
            ->havingRaw("SUM(CASE WHEN nilai.status_lulus = '{$tidakLulusValue}' THEN 1 ELSE 0 END) > 0")
            ->orderByRaw("SUM(CASE WHEN nilai.status_lulus = '{$tidakLulusValue}' THEN 1 ELSE 0 END) DESC, AVG(nilai.nilai_akhir) ASC")
            ->limit($limit)
            ->get()
            ->map(function ($r) {
                $tidakLulus = (int) $r->tidak_lulus;
                $total = (int) $r->total_mapel;
                $rasio = $total > 0 ? (float) round(($tidakLulus / $total) * 100, 1) : 0.0;

                return [
                    'nis' => $r->nis,
                    'nama_siswa' => $r->nama_siswa,
                    'kelas' => $r->kelas,
                    'rata_rata' => (float) round((float) $r->rata_rata, 2),
                    'total_mapel' => $total,
                    'lulus' => (int) $r->lulus,
                    'tidak_lulus' => $tidakLulus,
                    'rasio_tidak_lulus' => $rasio,
                ];
            })
            ->all();
    }
}
