<?php

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

    public function create(): Response
    {
        return Inertia::render('admin/kelas/create');
    }

    public function store(KelasRequest $request): RedirectResponse
    {
        Kelas::create($request->validated());

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kela): Response
    {
        return Inertia::render('admin/kelas/edit', [
            'kelas' => $kela,
        ]);
    }

    public function update(KelasRequest $request, Kelas $kela): RedirectResponse
    {
        $kela->update($request->validated());

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kela): RedirectResponse
    {
        if ($kela->siswa()->exists() || $kela->guruMengajar()->exists()) {
            return back()->with('error', "Tidak dapat menghapus kelas \"{$kela->nama}\" karena masih digunakan oleh {$kela->jumlah_siswa} siswa atau {$kela->jumlah_guru_mengajar} guru.");
        }

        $kela->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
