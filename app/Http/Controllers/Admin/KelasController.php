<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KelasRequest;
use App\Models\Kelas;
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
            ->withCount(['siswa', 'guruMengajar'])
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
        return Inertia::render('admin/kelas/create');
    }

    /**
     * Persist a new kelas record.
     *
     * @param  KelasRequest  $request  The validated form-request.
     * @return RedirectResponse Redirect to the kelas index with a success flash message.
     */
    public function store(KelasRequest $request): RedirectResponse
    {
        Kelas::create($request->validated());

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Show the form to edit an existing kelas.
     *
     * @param  Kelas  $kela  The kelas to edit, resolved by route-model binding (`Kela` alias used for URL disambiguation).
     * @return Response Inertia response rendering `admin/kelas/edit`.
     */
    public function edit(Kelas $kela): Response
    {
        return Inertia::render('admin/kelas/edit', [
            'kelas' => $kela,
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
        $kela->update($request->validated());

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
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

        $kela->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
