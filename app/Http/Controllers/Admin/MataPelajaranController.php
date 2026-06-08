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
     * Menampilkan daftar mata pelajaran ter-paginasi dengan filter pencarian.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter kueri `q`.
     * @return Response Respon Inertia yang merender view `admin/mata-pelajaran/index`.
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
     * Menampilkan form untuk membuat mata pelajaran baru.
     *
     * @return Response Respon Inertia yang merender view `admin/mata-pelajaran/create`.
     */
    public function create(): Response
    {
        return Inertia::render('admin/mata-pelajaran/create');
    }

    /**
     * Menyimpan data mata pelajaran baru.
     *
     * @param  MataPelajaranRequest  $request  Form request yang telah divalidasi.
     * @return RedirectResponse Pengalihan ke indeks mata pelajaran dengan pesan sukses flash.
     */
    public function store(MataPelajaranRequest $request): RedirectResponse
    {
        MataPelajaran::create($request->validated());

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit mata pelajaran yang sudah ada.
     *
     * @param  MataPelajaran  $mata_pelajaran  Mata pelajaran yang akan diedit, di-resolve oleh route-model binding.
     * @return Response Respon Inertia yang merender view `admin/mata-pelajaran/edit`.
     */
    public function edit(MataPelajaran $mata_pelajaran): Response
    {
        return Inertia::render('admin/mata-pelajaran/edit', [
            'mataPelajaran' => $mata_pelajaran,
        ]);
    }

    /**
     * Memperbarui data mata pelajaran yang sudah ada.
     *
     * @param  MataPelajaranRequest  $request  Form request yang telah divalidasi.
     * @param  MataPelajaran  $mata_pelajaran  Mata pelajaran yang akan diperbarui, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks mata pelajaran dengan pesan sukses flash.
     */
    public function update(MataPelajaranRequest $request, MataPelajaran $mata_pelajaran): RedirectResponse
    {
        $mata_pelajaran->update($request->validated());

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus data mata pelajaran. Menolak dengan pesan kesalahan flash jika mata pelajaran
     * tersebut masih direferensikan oleh baris data guru_mengajar atau nilai.
     *
     * @param  MataPelajaran  $mata_pelajaran  Mata pelajaran yang akan dihapus, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks mata pelajaran dengan pesan sukses atau kesalahan flash.
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
