<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KelasRequest;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KelasController extends Controller
{
    /**
     * Display the paginated kelas list with a search filter.
     *
     * Uses the `Kelas::search()` scope and `withCount` to load the related
     * siswa and guru-mengajar counts in a single query, avoiding N+1 in the
     * rendering pass.
     *
     * @param  Request  $request  Current HTTP request; reads the `q` query parameter.
     * @return Response Inertia response rendering `admin/kelas/index`.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('q', ''));

        $kelas = Kelas::query()
            ->search($search)
            ->orderBy('nama')
            ->withCount(['siswa', 'guruMengajar', 'mataPelajaran'])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/kelas/index', [
            'kelas' => $kelas,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to create a new kelas.
     *
     * @return Response Inertia response rendering `admin/kelas/create`.
     */
    public function create(): Response
    {
        return Inertia::render('admin/kelas/create', [
            'semua_mapel' => MataPelajaran::query()->select('id', 'nama')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Persist a new kelas record.
     *
     * @param  KelasRequest  $request  The validated form-request.
     * @return RedirectResponse Redirect to the kelas index with a success flash message.
     */
    public function store(KelasRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $mapel = $request->getMataPelajaran();

        $kelas = Kelas::create(['nama' => $data['nama']]);
        $this->syncAvailableMapel($kelas, $mapel);

        $message = $this->buildSyncMessage('ditambahkan', $kelas->nama, count($mapel), null);

        return redirect()->route('admin.kelas.index')->with('success', $message);
    }

    /**
     * Show the form to edit an existing kelas.
     *
     * @param  Kelas  $kela  The kelas to edit, resolved by route-model binding (`Kela` alias used for URL disambiguation).
     * @return Response Inertia response rendering `admin/kelas/edit`.
     */
    public function edit(Kelas $kela): Response
    {
        $selectedMapel = $kela->mataPelajaran()->orderBy('nama')->pluck('mata_pelajaran.id')->all();

        return Inertia::render('admin/kelas/edit', [
            'kelas' => $kela,
            'semua_mapel' => MataPelajaran::query()->select('id', 'nama')->orderBy('nama')->get(),
            'selected_mapel' => $selectedMapel,
        ]);
    }

    /**
     * Update an existing kelas record.
     *
     * @param  KelasRequest  $request  The validated form-request.
     * @param  Kelas  $kela  The kelas to update, resolved by route-model binding.
     * @return RedirectResponse Redirect to the kelas index with a success flash message.
     */
    public function update(KelasRequest $request, Kelas $kela): RedirectResponse
    {
        $data = $request->validated();
        $previousMapel = $kela->mataPelajaran()->orderBy('nama')->pluck('mata_pelajaran.id')->all();
        $kela->update(['nama' => $data['nama']]);

        if ($request->has('mata_pelajaran_id')) {
            $this->syncAvailableMapel($kela, $request->getMataPelajaran());
        }

        $message = $this->buildSyncMessage('diperbarui', $kela->nama, count($request->getMataPelajaran()), count($previousMapel));

        return redirect()->route('admin.kelas.index')->with('success', $message);
    }

    /**
     * Delete a kelas record. Refuses with an error flash if the kelas is
     * still referenced by siswa or guru-mengajar rows.
     *
     * @param  Kelas  $kela  The kelas to delete, resolved by route-model binding.
     * @return RedirectResponse Redirect to the kelas index with a success or error flash message.
     */
    public function destroy(Kelas $kela): RedirectResponse
    {
        if ($kela->siswa()->exists() || $kela->guruMengajar()->exists()) {
            return back()->with('error', "Tidak dapat menghapus kelas \"{$kela->nama}\" karena masih digunakan oleh {$kela->jumlah_siswa} siswa atau {$kela->jumlah_guru_mengajar} guru.");
        }

        $kela->mataPelajaran()->detach();
        $kela->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Replace the `kelas_mata_pelajaran` rows for the supplied kelas with
     * the supplied list of mata-pelajaran ids. Existing rows are detached;
     * new rows are attached in the supplied order.
     *
     * @param  Kelas  $kela  The kelas whose mapel allow-list is being replaced.
     * @param  array<int, int>  $mapel  Deduplicated, sorted list of mapel ids.
     */
    private function syncAvailableMapel(Kelas $kela, array $mapel): void
    {
        $kela->mataPelajaran()->detach();

        foreach ($mapel as $id) {
            $kela->mataPelajaran()->attach($id);
        }
    }

    /**
     * Build the Indonesian flash message for store/update. Includes a
     * summary of how many mapel are now allowed for the kelas, and a
     * "(sebelumnya: N)" tag on update when the count changed.
     *
     * @param  string  $verb  "ditambahkan" | "diperbarui"
     * @param  string  $kelasName  The kelas display name.
     * @param  int  $count  The new mapel count (after sync).
     * @param  int|null  $previousCount  The previous mapel count (only on update).
     */
    private function buildSyncMessage(string $verb, string $kelasName, int $count, ?int $previousCount): string
    {
        $base = "Kelas \"{$kelasName}\" berhasil {$verb}.";

        if ($count === 0) {
            return $base.' Belum ada mata pelajaran yang diizinkan untuk kelas ini — tambahkan mata pelajaran agar guru dapat di-assign mengajar di kelas ini.';
        }

        $summary = "Kelas \"{$kelasName}\" berhasil {$verb} dengan {$count} mata pelajaran diizinkan.";

        if ($previousCount !== null && $count !== $previousCount) {
            $summary .= ' (sebelumnya: '.$previousCount.')';
        }

        return $summary;
    }
}
