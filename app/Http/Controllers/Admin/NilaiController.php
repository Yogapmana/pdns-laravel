<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\NilaiUnlockLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for the admin "Buka Kunci Nilai" (unlock-nilai) intervention.
 *
 * Lists all currently-Final `Nilai` groupings (by guru + kelas + mata
 * pelajaran) and lets an admin revert one of them back to `Draft` while
 * recording the action (admin, reason, affected count) to
 * `nilai_unlock_log` for audit.
 */
class NilaiController extends Controller
{
    /**
     * Render the unlock page: a paginated table of Final combos (15/page)
     * + the latest 10 unlock-log entries.
     *
     * @param  Request  $request  Current HTTP request; reads `search`, `kelas`, and `page` query params.
     * @return Response Inertia response rendering `admin/nilai/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelas = $request->input('kelas');

        $combos = DB::table('nilai')
            ->join('guru', 'guru.id', '=', 'nilai.id_guru')
            ->where('nilai.status_validasi', Nilai::STATUS_FINAL)
            ->groupBy('nilai.id_guru', 'guru.nama_guru', 'nilai.kelas', 'nilai.mata_pelajaran')
            ->select([
                'nilai.id_guru',
                'guru.nama_guru',
                'nilai.kelas',
                'nilai.mata_pelajaran',
                DB::raw('COUNT(*) as total_siswa'),
                DB::raw('MAX(nilai.updated_at) as validated_at'),
            ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('guru.nama_guru', 'like', "%{$search}%")
                        ->orWhere('nilai.mata_pelajaran', 'like', "%{$search}%")
                        ->orWhere('nilai.kelas', 'like', "%{$search}%");
                });
            })
            ->when($kelas, fn ($q) => $q->where('nilai.kelas', $kelas))
            ->orderByDesc('validated_at')
            ->orderBy('nilai.kelas')
            ->orderBy('nilai.mata_pelajaran')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($r) => [
                'id_guru' => (int) $r->id_guru,
                'nama_guru' => $r->nama_guru,
                'kelas' => $r->kelas,
                'mata_pelajaran' => $r->mata_pelajaran,
                'total_siswa' => (int) $r->total_siswa,
                'validated_at' => $r->validated_at,
            ]);

        $kelasOptions = DB::table('nilai')
            ->where('status_validasi', Nilai::STATUS_FINAL)
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->all();

        $logs = NilaiUnlockLog::with(['admin:id,name', 'guru:id,nama_guru'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'admin_name' => $log->admin?->name ?? '(admin dihapus)',
                'nama_guru' => $log->guru?->nama_guru ?? '(guru dihapus)',
                'kelas' => $log->kelas,
                'mata_pelajaran' => $log->mata_pelajaran,
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
                'kelas' => $kelas,
            ],
        ]);
    }

    /**
     * Revert a single (guru, kelas, mata_pelajaran) combo from `Final`
     * back to `Draft`, logging the action.
     *
     * Validates the request payload (reason is mandatory, min 10 chars),
     * then inside a transaction: counts and updates the matching rows,
     * then writes a `NilaiUnlockLog` entry.
     *
     * @param  Request  $request  Current HTTP request carrying the target combo + reason.
     * @return RedirectResponse Redirect back with a success flash.
     */
    public function unlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_guru' => ['required', 'integer', 'exists:guru,id'],
            'kelas' => ['required', 'string', 'max:20'],
            'mata_pelajaran' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $guru = Guru::findOrFail($validated['id_guru']);

        $affected = DB::transaction(function () use ($validated) {
            $updated = Nilai::where('id_guru', $validated['id_guru'])
                ->where('kelas', $validated['kelas'])
                ->where('mata_pelajaran', $validated['mata_pelajaran'])
                ->where('status_validasi', Nilai::STATUS_FINAL)
                ->update(['status_validasi' => Nilai::STATUS_DRAFT]);

            NilaiUnlockLog::create([
                'id_admin' => auth()->id(),
                'id_guru' => $validated['id_guru'],
                'kelas' => $validated['kelas'],
                'mata_pelajaran' => $validated['mata_pelajaran'],
                'affected_rows' => $updated,
                'reason' => $validated['reason'],
            ]);

            return $updated;
        });

        return back()->with(
            $affected > 0 ? 'success' : 'info',
            $affected > 0
                ? "Nilai {$validated['mata_pelajaran']} kelas {$validated['kelas']} ({$guru->nama_guru}) berhasil dibuka. {$affected} baris dikembalikan ke Draft. Alasan tercatat di log."
                : "Tidak ada nilai berstatus Final untuk {$validated['mata_pelajaran']} kelas {$validated['kelas']} ({$guru->nama_guru}). Alasan tetap dicatat."
        );
    }
}
