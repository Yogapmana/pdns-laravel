<?php

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

    public function create(): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/create', [
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    public function store(SiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Siswa::create($data);

        return redirect()->route('admin.siswa.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function edit(Siswa $siswa): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/edit', [
            'siswa' => $siswa,
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();

        $siswa->update($data);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        $siswa->delete();

        return redirect()->route('admin.siswa.index')->with('success', 'Siswa dan seluruh nilai terkait berhasil dihapus.');
    }
}
