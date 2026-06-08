<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiUnlockLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk intervensi admin "Buka Kunci Nilai" (unlock-nilai).
 *
 * Menampilkan semua pengelompokan `Nilai` yang saat ini berstatus Final (berdasarkan guru + kelas + mata
 * pelajaran) dan memungkinkan admin mengembalikan salah satunya kembali ke status `Draft` sambil
 * mencatat tindakan tersebut (admin, alasan, jumlah baris terdampak) ke tabel `nilai_unlock_log` untuk audit.
 */
class NilaiController extends Controller
{
    /**
     * Merender halaman unlock: tabel ter-paginasi untuk kombinasi Final (15/halaman)
     * + 10 entri log unlock terbaru.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter kueri `search`, `kelas`, dan `page`.
     * @return Response Respon Inertia yang merender view `admin/nilai/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelasNama = $request->input('kelas');
        $kelasId = $kelasNama ? Kelas::where('nama', $kelasNama)->value('id') : null;

        $combos = DB::table('nilai')
            ->join('guru', 'guru.id', '=', 'nilai.id_guru')
            ->join('kelas', 'kelas.id', '=', 'nilai.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id', '=', 'nilai.mata_pelajaran_id')
            ->where('nilai.status_validasi', Nilai::STATUS_FINAL)
            ->groupBy('nilai.id_guru', 'guru.nama_guru', 'nilai.kelas_id', 'nilai.mata_pelajaran_id', 'kelas.nama', 'mata_pelajaran.nama')
            ->select([
                'nilai.id_guru',
                'guru.nama_guru',
                'nilai.kelas_id',
                'nilai.mata_pelajaran_id',
                'kelas.nama as kelas_nama',
                'mata_pelajaran.nama as mata_pelajaran_nama',
                DB::raw('COUNT(*) as total_siswa'),
                DB::raw('MAX(nilai.updated_at) as validated_at'),
            ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('guru.nama_guru', 'like', "%{$search}%")
                        ->orWhere('mata_pelajaran.nama', 'like', "%{$search}%")
                        ->orWhere('kelas.nama', 'like', "%{$search}%");
                });
            })
            ->when($kelasId, fn ($q) => $q->where('nilai.kelas_id', $kelasId))
            ->orderByDesc('validated_at')
            ->orderBy('kelas.nama')
            ->orderBy('mata_pelajaran.nama')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($r) => [
                'id_guru' => (int) $r->id_guru,
                'kelas_id' => (int) $r->kelas_id,
                'mata_pelajaran_id' => (int) $r->mata_pelajaran_id,
                'nama_guru' => $r->nama_guru,
                'kelas' => $r->kelas_nama,
                'mata_pelajaran' => $r->mata_pelajaran_nama,
                'total_siswa' => (int) $r->total_siswa,
                'validated_at' => $r->validated_at,
            ]);

        $kelasOptions = DB::table('nilai')
            ->join('kelas', 'kelas.id', '=', 'nilai.kelas_id')
            ->where('nilai.status_validasi', Nilai::STATUS_FINAL)
            ->distinct()
            ->orderBy('kelas.nama')
            ->pluck('kelas.nama')
            ->all();

        $logs = NilaiUnlockLog::with(['admin:id,name', 'guru:id,nama_guru', 'kelas:id,nama', 'mataPelajaran:id,nama'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'admin_name' => $log->admin?->name ?? '(admin dihapus)',
                'nama_guru' => $log->guru?->nama_guru ?? '(guru dihapus)',
                'kelas' => $log->kelas?->nama,
                'mata_pelajaran' => $log->mataPelajaran?->nama,
                'affected_rows' => $log->affected_rows,
                'reason' => $log->reason,
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            ])
            ->all();

        return Inertia::render('admin/nilai/index', [
            'combos' => $combos,
            'logs' => $logs,
            'kelas_options' => $kelasOptions,
            'filters' => [
                'search' => $search,
                'kelas' => $kelasNama,
            ],
        ]);
    }

    /**
     * Mengembalikan status satu kombinasi (guru, kelas, mata_pelajaran) dari `Final`
     * kembali ke `Draft`, dan mencatat tindakan tersebut.
     *
     * Memvalidasi data request (alasan bersifat wajib, minimal 10 karakter),
     * kemudian di dalam transaksi: menghitung dan memperbarui baris data yang cocok,
     * lalu menulis entri baru ke `NilaiUnlockLog`.
     *
     * @param  Request  $request  Request HTTP saat ini yang membawa kombinasi target + alasan.
     * @return RedirectResponse Pengalihan kembali dengan pesan flash sukses.
     */
    public function unlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_guru' => ['required', 'integer', 'exists:guru,id'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:mata_pelajaran,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $guru = Guru::findOrFail($validated['id_guru']);
        $kelasNama = Kelas::where('id', $validated['kelas_id'])->value('nama');
        $mapelNama = MataPelajaran::where('id', $validated['mata_pelajaran_id'])->value('nama');

        $affected = DB::transaction(function () use ($validated) {
            $updated = Nilai::where('id_guru', $validated['id_guru'])
                ->where('kelas_id', $validated['kelas_id'])
                ->where('mata_pelajaran_id', $validated['mata_pelajaran_id'])
                ->where('status_validasi', Nilai::STATUS_FINAL)
                ->update(['status_validasi' => Nilai::STATUS_DRAFT]);

            NilaiUnlockLog::create([
                'id_admin' => auth()->id(),
                'id_guru' => $validated['id_guru'],
                'kelas_id' => $validated['kelas_id'],
                'mata_pelajaran_id' => $validated['mata_pelajaran_id'],
                'affected_rows' => $updated,
                'reason' => $validated['reason'],
            ]);

            return $updated;
        });

        return back()->with(
            $affected > 0 ? 'success' : 'info',
            $affected > 0
                ? "Nilai {$mapelNama} kelas {$kelasNama} ({$guru->nama_guru}) berhasil dibuka. {$affected} baris dikembalikan ke Draft. Alasan tercatat di log."
                : "Tidak ada nilai berstatus Final untuk {$mapelNama} kelas {$kelasNama} ({$guru->nama_guru}). Alasan tetap dicatat."
        );
    }
}
