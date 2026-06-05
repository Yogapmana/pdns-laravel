<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the guru dashboard with personal teaching statistics.
     *
     * Aggregates the guru's mengajar combinations, the total siswa taught,
     * and counters for the guru's `Nilai` rows (total, draft, final, lulus,
     * tidak_lulus, average).
     *
     * @return Response Inertia response rendering `guru/dashboard`.
     */
    public function index(): Response
    {
        $guru = Guru::with('mengajar')->where('user_id', auth()->id())->firstOrFail();

        $kelasDiajar = $guru->mengajar()->distinct()->pluck('kelas')->all();
        $totalSiswa = Siswa::whereIn('kelas', $kelasDiajar)->count();

        $nilaiSaya = Nilai::where('id_guru', $guru->id);

        $totalNilai = $nilaiSaya->count();
        $jumlahDraft = $nilaiSaya->where('status_validasi', Nilai::STATUS_DRAFT)->count();
        $jumlahFinal = $nilaiSaya->where('status_validasi', Nilai::STATUS_FINAL)->count();
        $jumlahLulus = $nilaiSaya->where('status_lulus', Nilai::LULUS)->count();
        $jumlahTidakLulus = $nilaiSaya->where('status_lulus', Nilai::TIDAK_LULUS)->count();
        $rataRata = $nilaiSaya->avg('nilai_akhir');

        $mengajarList = $guru->mengajar()->orderBy('kelas')->orderBy('mata_pelajaran')->get();

        return Inertia::render('guru/dashboard', [
            'guru' => $guru,
            'stats' => [
                'total_siswa' => $totalSiswa,
                'total_nilai' => $totalNilai,
                'draft' => $jumlahDraft,
                'final' => $jumlahFinal,
                'lulus' => $jumlahLulus,
                'tidak_lulus' => $jumlahTidakLulus,
                'rata_rata' => $rataRata ? round((float) $rataRata, 2) : 0,
            ],
            'mengajar' => $mengajarList,
        ]);
    }
}
