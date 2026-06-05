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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
        $kelas = $request->input('kelas');
        $mapel = $request->input('mapel');

        $guru = Guru::query()
            ->with('user:id,username,is_active')
            ->with('mengajar:id_guru,kelas,mata_pelajaran')
            ->withCount('nilai')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('nama_guru', 'like', "%{$search}%");
            }))
            ->when($kelas, fn ($q) => $q->whereHas('mengajar', fn ($qq) => $qq->where('kelas', $kelas)))
            ->when($mapel, fn ($q) => $q->whereHas('mengajar', fn ($qq) => $qq->where('mata_pelajaran', $mapel)))
            ->orderBy('nama_guru')
            ->paginate(15)
            ->withQueryString();

        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/guru/index', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
            'filters' => [
                'search' => $search,
                'kelas' => $kelas,
                'mapel' => $mapel,
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
        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/guru/create', [
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    /**
     * Persist a new guru record together with their mengajar combinations.
     *
     * Wraps the two writes (`guru` insert and `guru_mengajar` sync) in a
     * database transaction so that a failure on either side rolls back both.
     *
     * @param  GuruRequest  $request  The validated form-request.
     * @return RedirectResponse Redirect to the guru index with a success flash message.
     */
    public function store(GuruRequest $request): RedirectResponse
    {
        $guru = DB::transaction(function () use ($request) {
            $guru = Guru::create(['nama_guru' => $request->validated()['nama_guru']]);
            $this->syncMengajar($guru, $request->getMengajar());

            return $guru;
        });

        return redirect()->route('admin.guru.index')->with('success', "Guru {$guru->nama_guru} berhasil ditambahkan dengan ".count($request->getMengajar()).' kombinasi mengajar.');
    }

    /**
     * Show the form to edit an existing guru.
     *
     * @param  Guru  $guru  The guru to edit, resolved by route-model binding.
     * @return Response Inertia response rendering `admin/guru/edit`.
     */
    public function edit(Guru $guru): Response
    {
        $guru->load(['user:id,username,is_active', 'mengajar']);
        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/guru/edit', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    /**
     * Update an existing guru record and re-sync their mengajar combinations.
     *
     * Both writes are wrapped in a database transaction. The sync helper
     * deletes the previous `guru_mengajar` rows and recreates them from the
     * submitted (deduplicated) pairs.
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
     * deduplicated manner (the `(kelas, mata_pelajaran)` pair must be unique
     * for a given guru).
     *
     * @param  Guru  $guru  The guru whose mengajar rows will be replaced.
     * @param  array<int, array{kelas: string, mata_pelajaran: string}>  $mengajar  The new mengajar pairs.
     */
    private function syncMengajar(Guru $guru, array $mengajar): void
    {
        $guru->mengajar()->delete();

        $unique = [];
        foreach ($mengajar as $row) {
            $key = $row['kelas'].'|'.$row['mata_pelajaran'];
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = true;
            $guru->mengajar()->create([
                'kelas' => $row['kelas'],
                'mata_pelajaran' => $row['mata_pelajaran'],
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
     * Toggle the `is_active` flag of the user account linked to this guru.
     *
     * A guru with no linked account is a no-op for the underlying user row;
     * the flash message still reflects the resulting state.
     *
     * @param  Guru  $guru  The guru whose account status will be toggled.
     * @return RedirectResponse Redirect back with a success flash message.
     */
    public function toggleActive(Guru $guru): RedirectResponse
    {
        $user = $guru->user;
        if ($user) {
            $user->update(['is_active' => ! $user->is_active]);
        }

        $status = $user?->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun guru berhasil {$status}.");
    }

    /**
     * Show the form to create a user account for a guru who does not yet have one.
     *
     * Aborts with 404 when the guru already has a `user_id` set.
     *
     * @param  Guru  $guru  The guru receiving a new account, resolved by route-model binding.
     * @return Response Inertia response rendering `admin/guru/create-account`.
     */
    public function createAccountForm(Guru $guru): Response
    {
        abort_if($guru->user_id !== null, 404, 'Guru ini sudah memiliki akun.');

        return Inertia::render('admin/guru/create-account', [
            'guru' => $guru,
        ]);
    }

    /**
     * Persist a new user account for the guru and link it to the guru row.
     *
     * Aborts with 404 when the guru already has a `user_id`. The password
     * is hashed before storage and the new user is created with the
     * `guru` role and `is_active = true` by default.
     *
     * @param  Request  $request  Current HTTP request; reads `username`, `name`, `password`, and `password_confirmation`.
     * @param  Guru  $guru  The guru receiving a new account, resolved by route-model binding.
     * @return RedirectResponse Redirect to the guru index with a success flash message containing the new username.
     */
    public function createAccount(Request $request, Guru $guru): RedirectResponse
    {
        abort_if($guru->user_id !== null, 404, 'Guru ini sudah memiliki akun.');

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(6), 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? $guru->nama_guru,
            'role' => User::ROLE_GURU,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        $guru->update(['user_id' => $user->id]);

        return redirect()->route('admin.guru.index')->with('success', "Akun untuk guru {$guru->nama_guru} berhasil dibuat (username: {$data['username']}).");
    }
}
