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
     * Display the paginated guru list with search, kelas, and mapel filters.
     *
     * Eager-loads the related `user` account and the `mengajar` combinations
     * to prevent N+1 queries when rendering the table. Uses `whereHas()` to
     * filter guru by their associated kelas/mapel.
     *
     * @param  Request  $request  Current HTTP request; reads `search`, `kelas`, and `mapel` query parameters.
     * @return Response Inertia response rendering `admin/guru/index`.
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
     * Show the form to create a new guru record.
     *
     * @return Response Inertia response rendering `admin/guru/create` with the available kelas and mapel lists.
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
     * Persist a new guru record together with their mengajar combinations and
     * a freshly-generated login account.
     *
     * All three writes (User, Guru, guru_mengajar) are wrapped in a single
     * database transaction so a failure on any side rolls everything back.
     * The username is auto-generated from the guru's name (lowercase, with
     * honorifics stripped and a numeric suffix appended when the chosen
     * username is already taken). The admin-supplied password is hashed
     * before storage. The created user is `is_active = true` by default.
     *
     * @param  GuruRequest  $request  The validated form-request (includes `password`).
     * @return RedirectResponse Redirect to the guru index with a success flash message containing the new username.
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
     * Show the form to edit an existing guru.
     *
     * @param  Guru  $guru  The guru to edit, resolved by route-model binding.
     * @return Response Inertia response rendering `admin/guru/edit`.
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
     * Update an existing guru record and re-sync their mengajar combinations.
     *
     * Both writes are wrapped in a database transaction. The sync helper
     * deletes the previous `guru_mengajar` rows and recreates them from the
     * submitted (deduplicated) pairs. The associated login account is not
     * touched; password resets and account activation are handled in
     * `Admin/AccountController`.
     *
     * @param  GuruRequest  $request  The validated form-request.
     * @param  Guru  $guru  The guru to update, resolved by route-model binding.
     * @return RedirectResponse Redirect to the guru index with a success flash message.
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
     * Replace the `guru_mengajar` rows for a guru with the supplied pairs.
     *
     * Existing rows are deleted first; new rows are then inserted in a
     * deduplicated manner (the `(kelas_id, mata_pelajaran_id)` pair must be
     * unique for a given guru).
     *
     * @param  Guru  $guru  The guru whose mengajar rows will be replaced.
     * @param  array<int, array{kelas_id: int, mata_pelajaran_id: int}>  $mengajar  The new mengajar pairs (using FK ids).
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
     * Delete a guru record. Refuses with an error flash if the guru has
     * ever input nilai (database-level RESTRICT on the foreign key), and
     * otherwise cascades the deletion to the linked user account and
     * mengajar combinations inside a transaction.
     *
     * @param  Guru  $guru  The guru to delete, resolved by route-model binding.
     * @return RedirectResponse Redirect to the guru index with a success or error flash message.
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
     * Generate a unique `users.username` from the guru's display name.
     *
     * Strategy (mirrors `DatabaseSeeder::generateGuruUsername`):
     *   - lowercase the input
     *   - split on whitespace
     *   - drop honorifics (`ibu`, `pak`, `bu`, `bpk`, `bapak`, `ibu.`)
     *   - concatenate the remaining tokens
     *
     * If the resulting base is already taken, append `2`, `3`, ... until
     * a free username is found. Falls back to `guru` when the cleaned
     * name is empty.
     *
     * @param  string  $namaGuru  The display name to derive a username from.
     * @return string The unique username to insert.
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
     * Build a nested `[kelas_id => [mapel_id, ...]]` map describing
     * which mata-pelajaran each kelas currently allows, based on the
     * `kelas_mata_pelajaran` pivot table.
     *
     * Used to populate the dependent mapel dropdown in the guru
     * create/edit forms. The map is keyed by `kelas.id` so the form can
     * submit FK ids directly.
     *
     * @return array<int, array<int, int>> Map keyed by `kelas.id` (cast to int), values are sorted `mata_pelajaran.id`.
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
