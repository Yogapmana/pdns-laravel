<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class NilaiController extends Controller
{
    /**
     * Display the read-only personal nilai page for the authenticated siswa.
     *
     * Loads the siswa's `Nilai` rows (eager-loading the `guru` profile),
     * groups them by the `(kelas, mata_pelajaran)` composite key, and
     * builds the per-mapel chart payload. Only rows that have been
     * validated (`status_validasi = Final`) are surfaced — Draft rows
     * remain hidden from the siswa until the guru locks them.
     *
     * @return Response Inertia response rendering `siswa/nilai/index`.
     */
    public function index(): Response
    {
        $siswa = Siswa::where('user_id', auth()->id())->firstOrFail();

        $nilai = Nilai::with('guru:id,nama_guru')
            ->where('nis', $siswa->nis)
            ->where('status_validasi', Nilai::STATUS_FINAL)
            ->get()
            ->groupBy(fn ($n) => $n->kelas.'|'.$n->mata_pelajaran);

        $guruMap = Guru::whereIn('id', $nilai->flatten()->pluck('id_guru')->unique())
            ->get()
            ->keyBy('id');

        $mapelList = $nilai->keys()
            ->map(fn ($k) => explode('|', $k)[1])
            ->unique()
            ->values();

        $chartData = $this->buildChartData($nilai);

        return Inertia::render('siswa/nilai/index', [
            'siswa' => $siswa,
            'nilai' => $nilai,
            'mapel_list' => $mapelList,
            'guru_map' => $guruMap,
            'chart_data' => $chartData,
        ]);
    }

    public function statistik(): Response
    {
        $siswa = Siswa::where('user_id', auth()->id())->firstOrFail();

        $nilai = Nilai::with('guru:id,nama_guru')
            ->where('nis', $siswa->nis)
            ->where('status_validasi', Nilai::STATUS_FINAL)
            ->get()
            ->groupBy(fn ($n) => $n->kelas.'|'.$n->mata_pelajaran);

        $guruMap = Guru::whereIn('id', $nilai->flatten()->pluck('id_guru')->unique())
            ->get()
            ->keyBy('id');

        $mapelList = $nilai->keys()
            ->map(fn ($k) => explode('|', $k)[1])
            ->unique()
            ->values();

        $chartData = $this->buildChartData($nilai);

        return Inertia::render('siswa/statistik/index', [
            'siswa' => $siswa,
            'nilai' => $nilai,
            'mapel_list' => $mapelList,
            'guru_map' => $guruMap,
            'chart_data' => $chartData,
        ]);
    }

    /**
     * Compute aggregated chart data from grouped nilai collection.
     *
     * Walks the `(kelas, mata_pelajaran)`-keyed collection once, accumulating
     * the sums required to compute averages for `tugas` / `uts` / `uas` /
     * `akhir`, the per-mapel rows, and the pass/fail counters.
     *
     * @param  Collection<string, Collection<int, Nilai>>  $nilai  Grouped nilai rows, keyed by `"kelas|mata_pelajaran"`.
     * @return array{
     *     overall: array{tugas: float|null, uts: float|null, uas: float|null, akhir: float|null, count: int},
     *     per_mapel: array<int, array{mapel: string, tugas: float|null, uts: float|null, uas: float|null, akhir: float|null, status: string|null, kkm: float}>,
     *     kkm: float,
     *     stats: array{total_mapel: int, lulus: int, tidak_lulus: int}
     * }  Aggregated chart data.
     */
    private function buildChartData($nilai): array
    {
        $kkm = Nilai::KKM;

        $perMapel = [];
        $sumTugas = 0;
        $sumUts = 0;
        $sumUas = 0;
        $sumAkhir = 0;
        $count = 0;
        $lulus = 0;
        $tidakLulus = 0;
        $totalMapel = 0;

        foreach ($nilai as $key => $list) {
            $item = $list->first();
            if (! $item || $item->nilai_akhir === null) {
                continue;
            }

            $tugas = $item->nilai_tugas !== null ? (float) $item->nilai_tugas : null;
            $uts = $item->nilai_uts !== null ? (float) $item->nilai_uts : null;
            $uas = $item->nilai_uas !== null ? (float) $item->nilai_uas : null;
            $akhir = (float) $item->nilai_akhir;

            $mapel = explode('|', $key)[1];
            $perMapel[] = [
                'mapel' => $mapel,
                'kelas' => $item->kelas,
                'tugas' => $tugas,
                'uts' => $uts,
                'uas' => $uas,
                'akhir' => $akhir,
                'status' => $item->status_lulus,
                'kkm' => $kkm,
            ];

            if ($tugas !== null) {
                $sumTugas += $tugas;
            }
            if ($uts !== null) {
                $sumUts += $uts;
            }
            if ($uas !== null) {
                $sumUas += $uas;
            }
            $sumAkhir += $akhir;
            $count++;
            $totalMapel++;
            if ($item->status_lulus === Nilai::LULUS) {
                $lulus++;
            } elseif ($item->status_lulus === Nilai::TIDAK_LULUS) {
                $tidakLulus++;
            }
        }

        $avg = fn (int|float $sum) => $count > 0 ? (float) round($sum / $count, 2) : null;

        $overallAkhir = $count > 0 ? (float) round($sumAkhir / $count, 2) : null;

        return [
            'overall' => [
                'tugas' => $avg($sumTugas),
                'uts' => $avg($sumUts),
                'uas' => $avg($sumUas),
                'akhir' => $overallAkhir,
                'count' => $count,
            ],
            'per_mapel' => $perMapel,
            'kkm' => $kkm,
            'stats' => [
                'total_mapel' => $totalMapel,
                'lulus' => $lulus,
                'tidak_lulus' => $tidakLulus,
            ],
        ];
    }
}
