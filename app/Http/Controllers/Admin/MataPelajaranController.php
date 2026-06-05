<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MataPelajaranRequest;
use App\Models\MataPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MataPelajaranController extends Controller
{
    /**
     * Display the paginated mata-pelajaran list with a search filter.
     *
     * @param  Request  $request  Current HTTP request; reads the `q` query parameter.
     * @return Response Inertia response rendering `admin/mata-pelajaran/index`.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('q', ''));

        $mataPelajaran = MataPelajaran::query()
            ->search($search)
            ->orderBy('nama')
            ->withCount(['guruMengajar', 'nilai'])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/mata-pelajaran/index', [
            'mataPelajaran' => $mataPelajaran,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to create a new mata pelajaran.
     *
     * @return Response Inertia response rendering `admin/mata-pelajaran/create`.
     */
    public function create(): Response
    {
        return Inertia::render('admin/mata-pelajaran/create');
    }

    /**
     * Persist a new mata pelajaran record.
     *
     * @param  MataPelajaranRequest  $request  The validated form-request.
     * @return RedirectResponse Redirect to the mata-pelajaran index with a success flash message.
     */
    public function store(MataPelajaranRequest $request): RedirectResponse
    {
        MataPelajaran::create($request->validated());

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    /**
     * Show the form to edit an existing mata pelajaran.
     *
     * @param  MataPelajaran  $mata_pelajaran  The mata pelajaran to edit, resolved by route-model binding.
     * @return Response Inertia response rendering `admin/mata-pelajaran/edit`.
     */
    public function edit(MataPelajaran $mata_pelajaran): Response
    {
        return Inertia::render('admin/mata-pelajaran/edit', [
            'mataPelajaran' => $mata_pelajaran,
        ]);
    }

    /**
     * Update an existing mata pelajaran record.
     *
     * @param  MataPelajaranRequest  $request  The validated form-request.
     * @param  MataPelajaran  $mata_pelajaran  The mata pelajaran to update, resolved by route-model binding.
     * @return RedirectResponse Redirect to the mata-pelajaran index with a success flash message.
     */
    public function update(MataPelajaranRequest $request, MataPelajaran $mata_pelajaran): RedirectResponse
    {
        $mata_pelajaran->update($request->validated());

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Delete a mata pelajaran record. Refuses with an error flash if the
     * mata pelajaran is still referenced by guru-mengajar or nilai rows.
     *
     * @param  MataPelajaran  $mata_pelajaran  The mata pelajaran to delete, resolved by route-model binding.
     * @return RedirectResponse Redirect to the mata-pelajaran index with a success or error flash message.
     */
    public function destroy(MataPelajaran $mata_pelajaran): RedirectResponse
    {
        if ($mata_pelajaran->guruMengajar()->exists() || $mata_pelajaran->nilai()->exists()) {
            return back()->with('error', "Tidak dapat menghapus mata pelajaran \"{$mata_pelajaran->nama}\" karena masih digunakan oleh {$mata_pelajaran->jumlah_guru_mengajar} guru atau {$mata_pelajaran->jumlah_nilai} nilai.");
        }

        $mata_pelajaran->delete();

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
