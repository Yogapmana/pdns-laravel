<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiswaRequest;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiswaController extends Controller
{
    /**
     * Display the paginated siswa list with optional search and class filters.
     *
     * Eager-loads the related `user` account (`id`, `username`, `is_active`)
     * to avoid N+1 queries when rendering the list. Query string parameters
     * are preserved across pagination links via `withQueryString()`.
     *
     * @param  Request  $request  Current HTTP request. Reads `search` and `kelas` query parameters.
     * @return Response Inertia response rendering `admin/siswa/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelas = $request->input('kelas');

        $siswa = Siswa::query()
            ->with('user:id,username,is_active')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('nis', 'like', "%{$search}%")
                    ->orWhere('nama_siswa', 'like', "%{$search}%");
            }))
            ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
            ->orderBy('kelas')
            ->orderBy('nis')
            ->paginate(15)
            ->withQueryString();

        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/index', [
            'siswa' => $siswa,
            'daftar_kelas' => $daftarKelas,
            'filters' => [
                'search' => $search,
                'kelas' => $kelas,
            ],
        ]);
    }

    /**
     * Show the form to create a new siswa.
     *
     * @return Response Inertia response rendering `admin/siswa/create` with the list of available kelas.
     */
    public function create(): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/create', [
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    /**
     * Persist a new siswa record.
     *
     * Validation is delegated to `SiswaRequest` which enforces the unique-NIS
     * rule and that `kelas` references an existing row in the `kelas` table.
     *
     * @param  SiswaRequest  $request  The validated form-request.
     * @return RedirectResponse Redirect to the siswa index with a success flash message.
     */
    public function store(SiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Siswa::create($data);

        return redirect()->route('admin.siswa.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    /**
     * Show the form to edit an existing siswa.
     *
     * Route-model binding resolves the `Siswa` instance from the `nis`
     * URL parameter (see `Siswa::getRouteKeyName()`).
     *
     * @param  Siswa  $siswa  The siswa to edit, resolved by route-model binding.
     * @return Response Inertia response rendering `admin/siswa/edit`.
     */
    public function edit(Siswa $siswa): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/edit', [
            'siswa' => $siswa,
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    /**
     * Update an existing siswa record.
     *
     * The `nis` field is immutable: `SiswaRequest::validated()` removes it
     * from the payload when the request method is `PUT`/`PATCH`.
     *
     * @param  SiswaRequest  $request  The validated form-request.
     * @param  Siswa  $siswa  The siswa to update, resolved by route-model binding.
     * @return RedirectResponse Redirect to the siswa index with a success flash message.
     */
    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();

        $siswa->update($data);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Delete a siswa record. Related `nilai` rows are removed automatically
     * via the database-level `ON DELETE CASCADE` foreign key.
     *
     * @param  Siswa  $siswa  The siswa to delete, resolved by route-model binding.
     * @return RedirectResponse Redirect to the siswa index with a success flash message.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        $siswa->delete();

        return redirect()->route('admin.siswa.index')->with('success', 'Siswa dan seluruh nilai terkait berhasil dihapus.');
    }
}
