<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NilaiController extends Controller
{
    /**
     * Display the guru's nilai-input page for a single (kelas, mata pelajaran) pair.
     *
     * Restricts the available kelas and mata-pelajaran options to the combos
     * present in the guru's `guru_mengajar` rows. When a valid combo is
     * selected, loads the siswa and any pre-existing `Nilai` rows so the form
     * is pre-populated for editing.
     *
     * @param  Request  $request  Current HTTP request; reads `kelas` and `mata_pelajaran` query parameters.
     * @return Response Inertia response rendering `guru/nilai/index`.
     */
    public function index(Request $request): Response
    {
        $guru = Guru::with(['mengajar.kelas:id,nama', 'mengajar.mataPelajaran:id,nama'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $kelas = $request->input('kelas');
        $mataPelajaran = $request->input('mata_pelajaran');
        $kelasId = $request->input('kelas_id');
        $mapelId = $request->input('mata_pelajaran_id');

        if ($kelas && ! $kelasId) {
            $kelasId = Kelas::where('nama', $kelas)->value('id');
        }
        if ($mataPelajaran && ! $mapelId) {
            $mapelId = MataPelajaran::where('nama', $mataPelajaran)->value('id');
        }

        $mengajar = $guru->mengajar;
        $daftarKelas = $mengajar
            ->map(fn ($m) => $m->kelas)
            ->filter()
            ->unique('id')
            ->sortBy('nama')
            ->values()
            ->map(fn ($k) => ['id' => (int) $k->id, 'nama' => $k->nama])
            ->all();
        $mapelByKelas = [];
        foreach ($mengajar->sortBy([
            ['kelas.nama', 'asc'],
            ['mataPelajaran.nama', 'asc'],
        ]) as $m) {
            $kelasKey = (string) $m->kelas?->id;
            if ($kelasKey === '') {
                continue;
            }
            $mapelByKelas[$kelasKey][] = [
                'id' => (int) $m->mataPelajaran?->id,
                'nama' => $m->mataPelajaran?->nama ?? '',
            ];
        }
        $daftarMapel = $kelasId && isset($mapelByKelas[(string) $kelasId]) ? $mapelByKelas[(string) $kelasId] : [];

        $siswa = collect();
        $nilaiMap = [];
        $statusValidasiGlobal = null;
        $hasMengajar = $guru->mengajar()->exists();

        if ($kelasId && $mapelId && $hasMengajar && $guru->mengajarDiKelasMapelId($kelasId, $mapelId)) {
            $siswa = Siswa::where('kelas_id', $kelasId)
                ->orderBy('nis')
                ->get();

            $existing = Nilai::where('id_guru', $guru->id)
                ->where('kelas_id', $kelasId)
                ->where('mata_pelajaran_id', $mapelId)
                ->whereIn('nis', $siswa->pluck('nis'))
                ->get()
                ->keyBy('nis');

            foreach ($existing as $item) {
                $nilaiMap[$item->nis] = $item;
            }

            $jumlahInputRows = $existing->whereNotNull('nilai_akhir')->count();
            $jumlahDraftRows = $existing->where('status_validasi', Nilai::STATUS_DRAFT)->count();

            $statusValidasiGlobal = $jumlahInputRows > 0 && $jumlahDraftRows === 0
                ? Nilai::STATUS_FINAL
                : Nilai::STATUS_DRAFT;
        }

        return Inertia::render('guru/nilai/index', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'mapel_by_kelas' => $mapelByKelas,
            'kelas' => $kelas,
            'kelas_id' => $kelasId,
            'mata_pelajaran' => $mataPelajaran,
            'mata_pelajaran_id' => $mapelId,
            'daftar_mapel' => $daftarMapel,
            'siswa' => $siswa,
            'nilai_map' => $nilaiMap,
            'status_validasi_global' => $statusValidasiGlobal,
            'has_mengajar' => $hasMengajar,
        ]);
    }

    /**
     * Persist (create or update) one or more `Nilai` rows for the guru.
     *
     * Validates the payload, aborts with 403 if the guru does not teach the
     * requested (kelas, mata_pelajaran) pair, then performs the writes inside
     * a transaction. Each row's `nilai_akhir` and `status_lulus` are
     * recomputed via the `Nilai` static helpers. Status is always set to
     * `Draft` on save.
     *
     * @param  Request  $request  Current HTTP request carrying the bulk nilai payload.
     * @return RedirectResponse Redirect back with a success flash message.
     */
    public function save(Request $request): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:mata_pelajaran,id'],
            'nilai' => ['required', 'array'],
            'nilai.*.nis' => ['required', 'string', 'exists:siswa,nis'],
            'nilai.*.nilai_tugas' => ['nullable', 'numeric', 'between:0,100'],
            'nilai.*.nilai_uts' => ['nullable', 'numeric', 'between:0,100'],
            'nilai.*.nilai_uas' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        if (! $guru->mengajarDiKelasMapelId($validated['kelas_id'], $validated['mata_pelajaran_id'])) {
            abort(403, 'Anda tidak mengajar kombinasi kelas dan mata pelajaran ini.');
        }

        DB::transaction(function () use ($validated, $guru) {
            foreach ($validated['nilai'] as $row) {
                $tugas = $row['nilai_tugas'] ?? null;
                $uts = $row['nilai_uts'] ?? null;
                $uas = $row['nilai_uas'] ?? null;

                if ($tugas === null && $uts === null && $uas === null) {
                    continue;
                }

                $tugas = (float) ($tugas ?? 0);
                $uts = (float) ($uts ?? 0);
                $uas = (float) ($uas ?? 0);

                if (! Nilai::validasiNilai($tugas) || ! Nilai::validasiNilai($uts) || ! Nilai::validasiNilai($uas)) {
                    throw ValidationException::withMessages([
                        'nilai' => 'Nilai harus dalam rentang 0-100.',
                    ]);
                }

                $akhir = Nilai::hitungNilaiAkhir($tugas, $uts, $uas);
                $status = Nilai::tentukanKelulusan((float) $akhir);

                Nilai::updateOrCreate(
                    [
                        'nis' => $row['nis'],
                        'id_guru' => $guru->id,
                        'kelas_id' => $validated['kelas_id'],
                        'mata_pelajaran_id' => $validated['mata_pelajaran_id'],
                    ],
                    [
                        'nilai_tugas' => $tugas,
                        'nilai_uts' => $uts,
                        'nilai_uas' => $uas,
                        'nilai_akhir' => $akhir,
                        'status_lulus' => $status,
                        'status_validasi' => Nilai::STATUS_DRAFT,
                    ]
                );
            }
        });

        return back()->with('success', 'Nilai berhasil disimpan sebagai Draft.');
    }

    /**
     * Lock all of the guru's non-empty `Nilai` rows for a given
     * (kelas, mata_pelajaran) pair by setting `status_validasi = Final`.
     *
     * @param  Request  $request  Current HTTP request; reads `kelas` and `mata_pelajaran`.
     * @return RedirectResponse Redirect back with a success flash message containing the number of affected rows.
     */
    public function validateFinal(Request $request): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:mata_pelajaran,id'],
        ]);

        if (! $guru->mengajarDiKelasMapelId($validated['kelas_id'], $validated['mata_pelajaran_id'])) {
            abort(403);
        }

        $updated = Nilai::where('id_guru', $guru->id)
            ->where('kelas_id', $validated['kelas_id'])
            ->where('mata_pelajaran_id', $validated['mata_pelajaran_id'])
            ->whereNotNull('nilai_akhir')
            ->update(['status_validasi' => Nilai::STATUS_FINAL]);

        return back()->with('success', "Nilai berhasil dikunci (Final). {$updated} baris nilai di-finalisasi.");
    }

    /**
     * Delete a single `Nilai` row owned by the guru.
     *
     * Aborts with 403 if the row's `id_guru` does not match the guru
     * associated with the authenticated user. Refuses to delete rows
     * already in `Final` status (return an error flash instead).
     *
     * @param  Nilai  $nilai  The nilai row to delete, resolved by route-model binding.
     * @return RedirectResponse Redirect back with a success or error flash message.
     */
    public function destroy(Nilai $nilai): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();

        if ($nilai->id_guru !== $guru->id) {
            abort(403);
        }

        if ($nilai->status_validasi === Nilai::STATUS_FINAL) {
            return back()->with('error', 'Nilai yang sudah Final tidak dapat dihapus.');
        }

        $nilai->delete();

        return back()->with('success', 'Nilai berhasil dihapus.');
    }

    /**
     * Display the guru's rekapitulasi nilai page for a (kelas, mata pelajaran) pair.
     *
     * Computes lulus / tidak_lulus / belum counts for the selected combination
     * and returns a per-siswa row with the associated `Nilai` (or null).
     *
     * @param  Request  $request  Current HTTP request; reads `kelas` and `mata_pelajaran` query parameters.
     * @return Response Inertia response rendering `guru/rekap/index`.
     */
    public function rekap(Request $request): Response
    {
        $guru = Guru::with(['mengajar.kelas:id,nama', 'mengajar.mataPelajaran:id,nama'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $kelas = $request->input('kelas');
        $mataPelajaran = $request->input('mata_pelajaran');
        $kelasId = $request->input('kelas_id');
        $mapelId = $request->input('mata_pelajaran_id');

        if ($kelas && ! $kelasId) {
            $kelasId = Kelas::where('nama', $kelas)->value('id');
        }
        if ($mataPelajaran && ! $mapelId) {
            $mapelId = MataPelajaran::where('nama', $mataPelajaran)->value('id');
        }

        $hasMengajar = $guru->mengajar()->exists();
        $mengajar = $guru->mengajar;

        $daftarKelas = $mengajar
            ->map(fn ($m) => $m->kelas)
            ->filter()
            ->unique('id')
            ->sortBy('nama')
            ->values()
            ->map(fn ($k) => ['id' => (int) $k->id, 'nama' => $k->nama])
            ->all();
        $mapelByKelas = [];
        foreach ($mengajar->sortBy([
            ['kelas.nama', 'asc'],
            ['mataPelajaran.nama', 'asc'],
        ]) as $m) {
            $kelasKey = (string) $m->kelas?->id;
            if ($kelasKey === '') {
                continue;
            }
            $mapelByKelas[$kelasKey][] = [
                'id' => (int) $m->mataPelajaran?->id,
                'nama' => $m->mataPelajaran?->nama ?? '',
            ];
        }
        $daftarMapel = $kelasId && isset($mapelByKelas[(string) $kelasId]) ? $mapelByKelas[(string) $kelasId] : [];

        $rows = collect();
        $stats = ['lulus' => 0, 'tidak_lulus' => 0, 'belum' => 0];

        if ($kelasId && $mapelId && $hasMengajar && $guru->mengajarDiKelasMapelId($kelasId, $mapelId)) {
            $siswaList = Siswa::where('kelas_id', $kelasId)->orderBy('nis')->get();
            $nilaiList = Nilai::where('id_guru', $guru->id)
                ->where('kelas_id', $kelasId)
                ->where('mata_pelajaran_id', $mapelId)
                ->whereIn('nis', $siswaList->pluck('nis'))
                ->get()
                ->keyBy('nis');

            foreach ($siswaList as $s) {
                $n = $nilaiList->get($s->nis);
                $rows->push([
                    'siswa' => $s,
                    'nilai' => $n,
                ]);
                if (! $n) {
                    $stats['belum']++;
                } elseif ($n->status_lulus === Nilai::LULUS) {
                    $stats['lulus']++;
                } else {
                    $stats['tidak_lulus']++;
                }
            }
        }

        return Inertia::render('guru/rekap/index', [
            'guru' => $guru,
            'kelas' => $kelas,
            'kelas_id' => $kelasId,
            'mata_pelajaran' => $mataPelajaran,
            'mata_pelajaran_id' => $mapelId,
            'daftar_kelas' => $daftarKelas,
            'mapel_by_kelas' => $mapelByKelas,
            'daftar_mapel' => $daftarMapel,
            'rows' => $rows,
            'stats' => $stats,
            'has_mengajar' => $hasMengajar,
        ]);
    }
}
