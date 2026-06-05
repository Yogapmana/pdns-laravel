<?php

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

    public function create(): Response
    {
        return Inertia::render('admin/mata-pelajaran/create');
    }

    public function store(MataPelajaranRequest $request): RedirectResponse
    {
        MataPelajaran::create($request->validated());

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mata_pelajaran): Response
    {
        return Inertia::render('admin/mata-pelajaran/edit', [
            'mataPelajaran' => $mata_pelajaran,
        ]);
    }

    public function update(MataPelajaranRequest $request, MataPelajaran $mata_pelajaran): RedirectResponse
    {
        $mata_pelajaran->update($request->validated());

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mata_pelajaran): RedirectResponse
    {
        if ($mata_pelajaran->guruMengajar()->exists() || $mata_pelajaran->nilai()->exists()) {
            return back()->with('error', "Tidak dapat menghapus mata pelajaran \"{$mata_pelajaran->nama}\" karena masih digunakan oleh {$mata_pelajaran->jumlah_guru_mengajar} guru atau {$mata_pelajaran->jumlah_nilai} nilai.");
        }

        $mata_pelajaran->delete();

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
