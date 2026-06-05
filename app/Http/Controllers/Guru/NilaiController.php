<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
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
    public function index(Request $request): Response
    {
        $guru = Guru::with('mengajar')->where('user_id', auth()->id())->firstOrFail();

        $kelas = $request->input('kelas');
        $mataPelajaran = $request->input('mata_pelajaran');

        $daftarKelas = $guru->mengajar()->distinct()->orderBy('kelas')->pluck('kelas')->all();
        $mapelByKelas = [];
        foreach ($guru->mengajar()->orderBy('kelas')->orderBy('mata_pelajaran')->get() as $m) {
            $mapelByKelas[$m->kelas][] = $m->mata_pelajaran;
        }
        $daftarMapel = $kelas && isset($mapelByKelas[$kelas]) ? $mapelByKelas[$kelas] : [];

        $siswa = collect();
        $nilaiMap = [];
        $statusValidasiGlobal = null;
        $hasMengajar = $guru->mengajar()->exists();

        if ($kelas && $mataPelajaran && $hasMengajar && $guru->mengajarDiKelasMapel($kelas, $mataPelajaran)) {
            $siswa = Siswa::where('kelas', $kelas)
                ->orderBy('nis')
                ->get();

            $existing = Nilai::where('id_guru', $guru->id)
                ->where('kelas', $kelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->whereIn('nis', $siswa->pluck('nis'))
                ->get()
                ->keyBy('nis');

            foreach ($existing as $item) {
                $nilaiMap[$item->nis] = $item;
            }

            $statusValidasiGlobal = $existing->first()?->status_validasi ?? Nilai::STATUS_DRAFT;
        }

        return Inertia::render('guru/nilai/index', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'mapel_by_kelas' => $mapelByKelas,
            'kelas' => $kelas,
            'mata_pelajaran' => $mataPelajaran,
            'daftar_mapel' => $daftarMapel,
            'siswa' => $siswa,
            'nilai_map' => $nilaiMap,
            'status_validasi_global' => $statusValidasiGlobal,
            'has_mengajar' => $hasMengajar,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'kelas' => ['required', 'string'],
            'mata_pelajaran' => ['required', 'string'],
            'nilai' => ['required', 'array'],
            'nilai.*.nis' => ['required', 'string', 'exists:siswa,nis'],
            'nilai.*.nilai_tugas' => ['nullable', 'numeric', 'between:0,100'],
            'nilai.*.nilai_uts' => ['nullable', 'numeric', 'between:0,100'],
            'nilai.*.nilai_uas' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        if (! $guru->mengajarDiKelasMapel($validated['kelas'], $validated['mata_pelajaran'])) {
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
                        'kelas' => $validated['kelas'],
                        'mata_pelajaran' => $validated['mata_pelajaran'],
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

    public function validateFinal(Request $request): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'kelas' => ['required', 'string'],
            'mata_pelajaran' => ['required', 'string'],
        ]);

        if (! $guru->mengajarDiKelasMapel($validated['kelas'], $validated['mata_pelajaran'])) {
            abort(403);
        }

        $updated = Nilai::where('id_guru', $guru->id)
            ->where('kelas', $validated['kelas'])
            ->where('mata_pelajaran', $validated['mata_pelajaran'])
            ->whereNotNull('nilai_akhir')
            ->update(['status_validasi' => Nilai::STATUS_FINAL]);

        return back()->with('success', "Nilai berhasil dikunci (Final). {$updated} baris nilai di-finalisasi.");
    }

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

    public function rekap(Request $request): Response
    {
        $guru = Guru::with('mengajar')->where('user_id', auth()->id())->firstOrFail();

        $kelas = $request->input('kelas');
        $mataPelajaran = $request->input('mata_pelajaran');
        $hasMengajar = $guru->mengajar()->exists();

        $daftarKelas = $guru->mengajar()->distinct()->orderBy('kelas')->pluck('kelas')->all();
        $mapelByKelas = [];
        foreach ($guru->mengajar()->orderBy('kelas')->orderBy('mata_pelajaran')->get() as $m) {
            $mapelByKelas[$m->kelas][] = $m->mata_pelajaran;
        }
        $daftarMapel = $kelas && isset($mapelByKelas[$kelas]) ? $mapelByKelas[$kelas] : [];

        $rows = collect();
        $stats = ['lulus' => 0, 'tidak_lulus' => 0, 'belum' => 0];

        if ($kelas && $mataPelajaran && $hasMengajar && $guru->mengajarDiKelasMapel($kelas, $mataPelajaran)) {
            $siswaList = Siswa::where('kelas', $kelas)->orderBy('nis')->get();
            $nilaiList = Nilai::where('id_guru', $guru->id)
                ->where('kelas', $kelas)
                ->where('mata_pelajaran', $mataPelajaran)
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
            'mata_pelajaran' => $mataPelajaran,
            'daftar_kelas' => $daftarKelas,
            'mapel_by_kelas' => $mapelByKelas,
            'daftar_mapel' => $daftarMapel,
            'rows' => $rows,
            'stats' => $stats,
            'has_mengajar' => $hasMengajar,
        ]);
    }
}
