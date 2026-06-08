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
     * Menampilkan daftar kelas ter-paginasi dengan filter pencarian.
     *
     * Menggunakan scope `Kelas::search()` dan `withCount` untuk memuat jumlah siswa,
     * guruMengajar, dan mataPelajaran terkait dalam satu kueri tunggal, guna menghindari query N+1
     * saat proses perenderan.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter kueri `q`.
     * @return Response Respon Inertia yang merender view `admin/kelas/index`.
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
     * Menampilkan form untuk membuat kelas baru.
     *
     * @return Response Respon Inertia yang merender view `admin/kelas/create`.
     */
    public function create(): Response
    {
        return Inertia::render('admin/kelas/create', [
            'semua_mapel' => MataPelajaran::query()->select('id', 'nama')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Menyimpan data kelas baru.
     *
     * @param  KelasRequest  $request  Form request yang telah divalidasi.
     * @return RedirectResponse Pengalihan ke indeks kelas dengan pesan sukses flash.
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
     * Menampilkan form untuk mengedit kelas yang sudah ada.
     *
     * @param  Kelas  $kela  Kelas yang akan diedit, di-resolve oleh route-model binding (alias `kela` digunakan untuk menghindari ambiguitas URL).
     * @return Response Respon Inertia yang merender view `admin/kelas/edit`.
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
     * Memperbarui data kelas yang sudah ada.
     *
     * @param  KelasRequest  $request  Form request yang telah divalidasi.
     * @param  Kelas  $kela  Kelas yang akan diperbarui, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks kelas dengan pesan sukses flash.
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
     * Menghapus data kelas. Menolak dengan pesan kesalahan flash jika kelas tersebut
     * masih direferensikan oleh baris data siswa atau guru_mengajar.
     *
     * @param  Kelas  $kela  Kelas yang akan dihapus, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks kelas dengan pesan sukses atau kesalahan flash.
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
     * Mengganti baris data `kelas_mata_pelajaran` untuk kelas yang diberikan dengan
     * daftar ID mata pelajaran yang diberikan. Baris yang ada akan dilepas (detached);
     * baris baru kemudian dikaitkan (attached) sesuai urutan yang diberikan.
     *
     * @param  Kelas  $kela  Kelas yang daftar izin mapelnya akan diganti.
     * @param  array<int, int>  $mapel  Daftar ID mapel yang telah dideduplikasi dan diurutkan.
     */
    private function syncAvailableMapel(Kelas $kela, array $mapel): void
    {
        $kela->mataPelajaran()->detach();

        foreach ($mapel as $id) {
            $kela->mataPelajaran()->attach($id);
        }
    }

    /**
     * Membangun pesan flash bahasa Indonesia untuk operasi store/update. Menyertakan
     * ringkasan tentang berapa banyak mapel yang diperbolehkan untuk kelas tersebut, dan
     * label "(sebelumnya: N)" pada operasi update saat jumlahnya berubah.
     *
     * @param  string  $verb  Kata kerja operasi: "ditambahkan" | "diperbarui".
     * @param  string  $kelasName  Nama tampilan kelas.
     * @param  int  $count  Jumlah mapel baru (setelah sinkronisasi).
     * @param  int|null  $previousCount  Jumlah mapel sebelumnya (hanya diisi pada operasi update).
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
