<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuruRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class GuruController extends Controller
{
    /**
     * Menampilkan daftar guru ter-paginasi dengan filter pencarian, kelas, dan mapel.
     *
     * Eager-load akun `user` terkait dan kombinasi `mengajar` untuk mencegah kueri N+1
     * saat merender tabel. Menggunakan `whereHas()` untuk memfilter guru berdasarkan kelas/mapel
     * yang terkait dengan mereka.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter kueri `search`, `kelas`, dan `mapel`.
     * @return Response Respon Inertia yang merender view `admin/guru/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelasNama = $request->input('kelas');
        $mapelNama = $request->input('mapel');
        $kelasId = $kelasNama ? Kelas::where('nama', $kelasNama)->value('id') : null;
        $mapelId = $mapelNama ? MataPelajaran::where('nama', $mapelNama)->value('id') : null;

        $guru = Guru::query()
            ->with('user:id,username,is_active')
            ->with(['mengajar.kelas:id,nama', 'mengajar.mataPelajaran:id,nama'])
            ->withCount('nilai')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('nama_guru', 'like', "%{$search}%");
            }))
            ->when($kelasId, fn ($q) => $q->whereHas('mengajar', fn ($qq) => $qq->where('kelas_id', $kelasId)))
            ->when($mapelId, fn ($q) => $q->whereHas('mengajar', fn ($qq) => $qq->where('mata_pelajaran_id', $mapelId)))
            ->orderBy('nama_guru')
            ->paginate(15)
            ->withQueryString();

        $daftarKelas = Kelas::pluckIdNamaOrdered();
        $daftarMapel = MataPelajaran::pluckIdNamaOrdered();

        return Inertia::render('admin/guru/index', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
            'filters' => [
                'search' => $search,
                'kelas' => $kelasNama,
                'mapel' => $mapelNama,
            ],
        ]);
    }

    /**
     * Menampilkan form untuk membuat data guru baru.
     *
     * @return Response Respon Inertia yang merender view `admin/guru/create` dengan daftar kelas dan mapel yang tersedia.
     */
    public function create(): Response
    {
        $daftarKelas = Kelas::pluckIdNamaOrdered();
        $daftarMapel = MataPelajaran::pluckIdNamaOrdered();
        $mapelByKelas = $this->buildMapelByKelas();

        return Inertia::render('admin/guru/create', [
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
            'mapel_by_kelas' => $mapelByKelas,
        ]);
    }

    /**
     * Menyimpan data guru baru beserta kombinasi mengajar dan akun login yang baru dibuat.
     *
     * Ketiga proses tulis (User, Guru, guru_mengajar) dibungkus dalam satu transaksi database
     * sehingga kegagalan di bagian mana pun akan membatalkan (rollback) seluruhnya.
     * Username dibuat otomatis dari nama guru (huruf kecil, gelar dihilangkan, dan akhiran numerik
     * ditambahkan jika username pilihan sudah digunakan). Password yang diinput oleh admin di-hash
     * sebelum disimpan. Pengguna yang dibuat berstatus aktif (`is_active = true`) secara default.
     *
     * @param  GuruRequest  $request  Form request yang telah divalidasi (termasuk `password`).
     * @return RedirectResponse Pengalihan ke indeks guru dengan pesan sukses flash berisi username baru.
     */
    public function store(GuruRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $username = $this->generateUniqueUsername($data['nama_guru']);

        DB::transaction(function () use ($data, $username, $request) {
            $user = User::create([
                'username' => $username,
                'name' => $data['nama_guru'],
                'role' => User::ROLE_GURU,
                'is_active' => true,
                'password' => Hash::make($data['password']),
            ]);

            $guru = Guru::create([
                'user_id' => $user->id,
                'nama_guru' => $data['nama_guru'],
            ]);

            $this->syncMengajar($guru, $request->getMengajar());
        });

        $jumlahMengajar = count($request->getMengajar());

        return redirect()->route('admin.guru.index')->with(
            'success',
            "Guru {$data['nama_guru']} berhasil ditambahkan dengan {$jumlahMengajar} kombinasi mengajar. Akun login otomatis dibuat dengan username: {$username}."
        );
    }

    /**
     * Menampilkan form untuk mengedit guru yang sudah ada.
     *
     * @param  Guru  $guru  Data guru yang akan diedit, di-resolve oleh route-model binding.
     * @return Response Respon Inertia yang merender view `admin/guru/edit`.
     */
    public function edit(Guru $guru): Response
    {
        $guru->load(['user:id,username,is_active', 'mengajar.kelas:id,nama', 'mengajar.mataPelajaran:id,nama']);
        $daftarKelas = Kelas::pluckIdNamaOrdered();
        $daftarMapel = MataPelajaran::pluckIdNamaOrdered();
        $mapelByKelas = $this->buildMapelByKelas();

        return Inertia::render('admin/guru/edit', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
            'mapel_by_kelas' => $mapelByKelas,
        ]);
    }

    /**
     * Memperbarui data guru yang sudah ada dan mensinkronisasikan ulang kombinasi mengajar mereka.
     *
     * Kedua proses tulis dibungkus dalam transaksi database. Fungsi pembantu sync akan menghapus
     * baris `guru_mengajar` sebelumnya dan membuatnya kembali dari pasangan data (yang sudah dideduplikasi)
     * yang dikirimkan. Akun login terkait tidak diubah; reset password dan aktivasi akun ditangani di
     * `Admin/AccountController`.
     *
     * @param  GuruRequest  $request  Form request yang telah divalidasi.
     * @param  Guru  $guru  Data guru yang akan diperbarui, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks guru dengan pesan sukses flash.
     */
    public function update(GuruRequest $request, Guru $guru): RedirectResponse
    {
        DB::transaction(function () use ($request, $guru) {
            $guru->update(['nama_guru' => $request->validated()['nama_guru']]);
            $this->syncMengajar($guru, $request->getMengajar());
        });

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    /**
     * Mengganti baris data `guru_mengajar` untuk seorang guru dengan pasangan data yang diberikan.
     *
     * Baris data yang ada akan dihapus terlebih dahulu; baris data baru kemudian dimasukkan secara
     * dideduplikasi (pasangan `(kelas_id, mata_pelajaran_id)` harus unik untuk guru tertentu).
     *
     * @param  Guru  $guru  Guru yang baris mengajarnya akan diganti.
     * @param  array<int, array{kelas_id: int, mata_pelajaran_id: int}>  $mengajar  Pasangan mengajar baru (menggunakan ID foreign key).
     */
    private function syncMengajar(Guru $guru, array $mengajar): void
    {
        $guru->mengajar()->delete();

        $unique = [];
        foreach ($mengajar as $row) {
            $key = $row['kelas_id'].'|'.$row['mata_pelajaran_id'];
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = true;
            $guru->mengajar()->create([
                'kelas_id' => $row['kelas_id'],
                'mata_pelajaran_id' => $row['mata_pelajaran_id'],
            ]);
        }
    }

    /**
     * Menghapus data guru. Menolak dengan pesan kesalahan flash jika guru tersebut sudah pernah
     * menginput nilai (karena RESTRICT pada level database untuk foreign key), dan jika tidak,
     * akan menghapus akun user terkait secara berantai serta kombinasi mengajar mereka dalam satu transaksi.
     *
     * @param  Guru  $guru  Guru yang akan dihapus, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan ke indeks guru dengan pesan sukses atau kesalahan flash.
     */
    public function destroy(Guru $guru): RedirectResponse
    {
        if ($guru->nilai()->exists()) {
            return back()->with('error', 'Guru tidak dapat dihapus karena sudah pernah menginput nilai. Gunakan fitur Nonaktifkan Akun sebagai gantinya.');
        }

        DB::transaction(function () use ($guru) {
            $guru->user?->delete();
            $guru->mengajar()->delete();
            $guru->delete();
        });

        return redirect()->route('admin.guru.index')->with('success', 'Guru berhasil dihapus.');
    }

    /**
     * Membuat `users.username` yang unik dari nama tampilan guru.
     *
     * Strategi (menyerupai `DatabaseSeeder::generateGuruUsername`):
     *   - ubah input menjadi huruf kecil
     *   - pisahkan berdasarkan spasi
     *   - hapus panggilan penghormatan (`ibu`, `pak`, `bu`, `bpk`, `bapak`, `ibu.`)
     *   - gabungkan token yang tersisa
     *
     * Jika nama dasar yang dihasilkan sudah digunakan, tambahkan `2`, `3`, ... hingga
     * ditemukan username yang kosong. Menggunakan default `guru` jika nama bersih bernilai kosong.
     *
     * @param  string  $namaGuru  Nama tampilan guru yang akan dijadikan dasar username.
     * @return string Username unik yang akan disimpan.
     */
    private function generateUniqueUsername(string $namaGuru): string
    {
        $honorifics = ['ibu', 'pak', 'bu', 'bpk', 'bapak', 'ibu.'];
        $parts = preg_split('/\s+/', strtolower(trim($namaGuru))) ?: [];
        $parts = array_values(array_filter(
            $parts,
            fn (string $p): bool => ! in_array(rtrim($p, '.'), $honorifics, true) && $p !== ''
        ));
        $base = implode('', $parts);
        if ($base === '') {
            $base = 'guru';
        }

        $username = $base;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $counter++;
            $username = $base.$counter;
        }

        return $username;
    }

    /**
     * Membangun peta bersarang `[kelas_id => [mapel_id, ...]]` yang menjelaskan
     * mata pelajaran mana saja yang diperbolehkan untuk setiap kelas saat ini,
     * berdasarkan tabel pivot `kelas_mata_pelajaran`.
     *
     * Digunakan untuk mengisi dropdown mapel dependen pada form buat/edit guru.
     * Peta menggunakan kunci `kelas.id` sehingga form dapat mengirimkan ID foreign key secara langsung.
     *
     * @return array<int, array<int, int>> Peta dengan kunci `kelas.id` (di-cast ke int), nilainya adalah ID `mata_pelajaran` yang terurut.
     */
    private function buildMapelByKelas(): array
    {
        $rows = DB::table('kelas_mata_pelajaran')
            ->join('kelas', 'kelas.id', '=', 'kelas_mata_pelajaran.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id', '=', 'kelas_mata_pelajaran.mata_pelajaran_id')
            ->orderBy('kelas.nama')
            ->orderBy('mata_pelajaran.nama')
            ->get(['kelas_mata_pelajaran.kelas_id as kelas_id', 'kelas_mata_pelajaran.mata_pelajaran_id as mapel_id', 'mata_pelajaran.nama as mapel_nama']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->kelas_id][] = [
                'id' => (int) $r->mapel_id,
                'nama' => $r->mapel_nama,
            ];
        }

        return $map;
    }
}
