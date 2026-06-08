<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiswaRequest;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SiswaController extends Controller
{
    /**
     * Menampilkan daftar siswa ter-paginasi dengan filter pencarian dan kelas opsional.
     *
     * Eager-load akun `user` terkait (`id`, `username`, `is_active`, `created_at`)
     * dan agregasi `nilai_count` untuk menghindari query N+1 saat merender daftar dan detail laci (drawer).
     * Parameter query string dipertahankan di seluruh link paginasi melalui `withQueryString()`.
     *
     * @param  Request  $request  Request HTTP saat ini. Membaca parameter kueri `search` dan `kelas`.
     * @return Response Respon Inertia yang merender view `admin/siswa/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelasNama = $request->input('kelas');
        $kelasId = $kelasNama ? Kelas::where('nama', $kelasNama)->value('id') : null;

        $siswa = Siswa::query()
            ->with(['user:id,username,is_active,created_at', 'kelas:id,nama'])
            ->withCount('nilai')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('nis', 'like', "%{$search}%")
                    ->orWhere('nama_siswa', 'like', "%{$search}%");
            }))
            ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
            ->orderBy('kelas_id')
            ->orderBy('nis')
            ->paginate(15)
            ->withQueryString();

        $daftarKelas = Kelas::pluckIdNamaOrdered();

        return Inertia::render('admin/siswa/index', [
            'siswa' => $siswa,
            'daftar_kelas' => $daftarKelas,
            'filters' => [
                'search' => $search,
                'kelas' => $kelasNama,
            ],
        ]);
    }

    /**
     * Menampilkan form untuk membuat siswa baru.
     *
     * @return Response Respon Inertia yang merender view `admin/siswa/create` dengan daftar kelas yang tersedia.
     */
    public function create(): Response
    {
        $daftarKelas = Kelas::pluckIdNamaOrdered();

        return Inertia::render('admin/siswa/create', [
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    /**
     * Menyimpan data siswa baru beserta akun login yang baru dibuat.
     *
     * Kedua proses tulis (User, Siswa) dibungkus dalam satu transaksi database sehingga kegagalan
     * di salah satu pihak akan membatalkan keduanya. `username` diisi dengan `nis` (menyerupai seeder),
     * `name` adalah nama tampilan siswa, dan `password` adalah nilai yang diinput oleh admin dari form.
     * User baru berstatus aktif (`is_active = true`) secara default.
     *
     * @param  SiswaRequest  $request  Form request yang telah divalidasi (termasuk `password`).
     * @return RedirectResponse Pengalihan ke indeks siswa dengan pesan sukses flash berisi username baru.
     */
    public function store(SiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $nis = $data['nis'];

        DB::transaction(function () use ($data, $nis) {
            $user = User::create([
                'username' => $nis,
                'name' => $data['nama_siswa'],
                'role' => User::ROLE_SISWA,
                'is_active' => true,
                'password' => Hash::make($data['password']),
            ]);

            Siswa::create([
                'nis' => $nis,
                'user_id' => $user->id,
                'nama_siswa' => $data['nama_siswa'],
                'kelas_id' => $data['kelas_id'] ?? null,
            ]);
        });

        return redirect()->route('admin.siswa.index')->with(
            'success',
            "Siswa {$data['nama_siswa']} berhasil ditambahkan. Akun login otomatis dibuat dengan username: {$nis}."
        );
    }

    /**
     * Menampilkan form untuk mengedit siswa yang sudah ada.
     *
     * Route-model binding men-resolve instance `Siswa` dari parameter URL `nis`
     * (lihat `Siswa::getRouteKeyName()`).
     *
     * @param  Siswa  $siswa  Siswa yang akan diedit, di-resolve oleh route-model binding.
     * @return Response Respon Inertia yang merender view `admin/siswa/edit`.
     */
    public function edit(Siswa $siswa): Response
    {
        $daftarKelas = Kelas::pluckIdNamaOrdered();

        return Inertia::render('admin/siswa/edit', [
            'siswa' => $siswa,
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    /**
     * Memperbarui data siswa yang sudah ada. Kolom `nis` tidak dapat diubah (immutable):
     * form request akan menghapusnya dari payload pada request PUT/PATCH.
     *
     * Jika password yang dikirim tidak kosong, password akun login yang ditautkan
     * akan direset dalam transaksi yang sama. Mengirim password kosong tidak akan mengubah
     * password yang sudah ada.
     *
     * @param  SiswaRequest  $request  Form request yang telah divalidasi.
     * @param  Siswa  $siswa  Siswa yang akan diperbarui, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks siswa dengan pesan sukses flash.
     */
    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();
        $password = $data['password'] ?? null;
        unset($data['password']);

        DB::transaction(function () use ($siswa, $data, $password) {
            $siswa->update($data);
            if ($password !== null && $siswa->user) {
                $siswa->user->update(['password' => Hash::make($password)]);
            }
        });

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data siswa. Baris data `nilai` terkait akan dihapus secara otomatis
     * melalui foreign key `ON DELETE CASCADE` pada tingkat database.
     *
     * @param  Siswa  $siswa  Siswa yang akan dihapus, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks siswa dengan pesan sukses flash.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        DB::transaction(function () use ($siswa) {
            $siswa->user?->delete();
            $siswa->delete();
        });

        return redirect()->route('admin.siswa.index')->with('success', 'Siswa dan seluruh nilai terkait berhasil dihapus.');
    }
}
