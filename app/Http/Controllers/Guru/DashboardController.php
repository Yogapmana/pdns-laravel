<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the guru dashboard with personal teaching statistics.
     *
     * Aggregates the guru's mengajar combinations, the total siswa taught,
     * overall nilai counters (total, draft, final, lulus, tidak_lulus, average),
     * a per-combo breakdown (jumlah_siswa, jumlah_input, jumlah_final,
     * jumlah_draft) so the guru can see exactly which (kelas, mapel) pairs
     * still need work, and two notifikasi lists surfaced as alert cards at
     * the top of the page: combos that have not been fully inputed yet, and
     * combos that are complete but still in Draft status (i.e. awaiting
     * Validasi Final).
     *
     * @return Response Inertia response rendering `guru/dashboard`.
     */
    public function index(): Response
    {
        $guru = Guru::with('mengajar')->where('user_id', auth()->id())->firstOrFail();

        $kelasDiajar = $guru->mengajar()->distinct()->pluck('kelas')->all();
        $totalSiswa = Siswa::whereIn('kelas', $kelasDiajar)->count();

        $nilaiBase = Nilai::where('id_guru', $guru->id);

        $totalNilai = (clone $nilaiBase)->count();
        $jumlahDraft = (clone $nilaiBase)->where('status_validasi', Nilai::STATUS_DRAFT)->count();
        $jumlahFinal = (clone $nilaiBase)->where('status_validasi', Nilai::STATUS_FINAL)->count();
        $jumlahLulus = (clone $nilaiBase)->where('status_lulus', Nilai::LULUS)->count();
        $jumlahTidakLulus = (clone $nilaiBase)->where('status_lulus', Nilai::TIDAK_LULUS)->count();
        $rataRata = (clone $nilaiBase)->avg('nilai_akhir');

        $mengajarList = $guru->mengajar()->orderBy('kelas')->orderBy('mata_pelajaran')->get();
        $perComboStats = $this->buildPerComboStats($guru);
        $notifikasi = $this->buildNotifikasi($guru, $perComboStats);

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
            'per_combo_stats' => $perComboStats,
            'notifikasi' => $notifikasi,
        ]);
    }

    /**
     * Build a per-combo breakdown for the guru's mengajar rows.
     *
     * For every (kelas, mata_pelajaran) row in `guru_mengajar` owned by
     * `$guru`, return an entry containing the jumlah_siswa in that kelas,
     * the number of `Nilai` rows the guru has for the combo that have a
     * non-null `nilai_akhir` (jumlah_input), and the draft/final split
     * among those rows. Combos are sorted by kelas, then mata_pelajaran.
     *
     * @param  Guru  $guru  The authenticated guru.
     * @return array<int, array<string, mixed>>
     */
    private function buildPerComboStats(Guru $guru): array
    {
        /** @var Collection<int, GuruMengajar> $mengajar */
        $mengajar = $guru->mengajar()
            ->orderBy('kelas')
            ->orderBy('mata_pelajaran')
            ->get();

        /** @var Collection<string, int> $siswaCounts */
        $siswaCounts = Siswa::query()
            ->selectRaw('kelas, COUNT(*) as total')
            ->groupBy('kelas')
            ->pluck('total', 'kelas');

        $stats = [];

        foreach ($mengajar as $m) {
            $jumlahSiswa = (int) ($siswaCounts[$m->kelas] ?? 0);

            $nilaiRows = Nilai::where('id_guru', $guru->id)
                ->where('kelas', $m->kelas)
                ->where('mata_pelajaran', $m->mata_pelajaran)
                ->get(['status_validasi', 'nilai_akhir']);

            $jumlahInput = $nilaiRows->whereNotNull('nilai_akhir')->count();
            $jumlahFinalRows = $nilaiRows->where('status_validasi', Nilai::STATUS_FINAL)->count();
            $jumlahDraftRows = $nilaiRows->where('status_validasi', Nilai::STATUS_DRAFT)->count();

            $stats[] = [
                'id_mengajar' => (int) $m->id,
                'kelas' => $m->kelas,
                'mata_pelajaran' => $m->mata_pelajaran,
                'jumlah_siswa' => $jumlahSiswa,
                'jumlah_input' => $jumlahInput,
                'jumlah_final' => $jumlahFinalRows,
                'jumlah_draft' => $jumlahDraftRows,
            ];
        }

        return $stats;
    }

    /**
     * Build the two notifikasi lists for the guru's mengajar combos.
     *
     * A combo is considered:
     *   - "belum_diinput" (yellow) when at least one siswa has no nilai row yet.
     *   - "masih_draft"   (red)    when all siswa have a nilai row but the
     *                                 combo has not been validated to Final
     *                                 (i.e. any row still has status_validasi = Draft).
     *
     * Combos with no siswa at all (e.g. brand-new empty kelas) are
     * suppressed from both lists because there is nothing to input.
     *
     * @param  Guru  $guru  The authenticated guru.
     * @param  array<int, array<string, mixed>>  $perComboStats  Output of {@see self::buildPerComboStats()}.
     * @return array{belum_diinput: array<int, array<string, mixed>>, masih_draft: array<int, array<string, mixed>>}
     */
    private function buildNotifikasi(Guru $guru, array $perComboStats): array
    {
        $belumDiinput = [];
        $masihDraft = [];

        foreach ($perComboStats as $combo) {
            if ($combo['jumlah_siswa'] === 0) {
                continue;
            }

            if ($combo['jumlah_input'] < $combo['jumlah_siswa']) {
                $belumDiinput[] = $combo + ['sisa' => $combo['jumlah_siswa'] - $combo['jumlah_input']];
            } elseif ($combo['jumlah_draft'] > 0) {
                $masihDraft[] = $combo;
            }
        }

        return [
            'belum_diinput' => $belumDiinput,
            'masih_draft' => $masihDraft,
        ];
    }
}
