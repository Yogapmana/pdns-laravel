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
     * Display the paginated siswa list with optional search and class filters.
     *
     * Eager-loads the related `user` account (`id`, `username`, `is_active`)
     * to avoid N+1 queries when rendering the list. Query string parameters
     * are preserved across pagination links via `withQueryString()`.
     *
     * @param  Request  $request  Current HTTP request. Reads `search` and `kelas` query parameters.
     * @return Response Inertia response rendering `admin/siswa/index`.
     */
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

    /**
     * Show the form to create a new siswa.
     *
     * @return Response Inertia response rendering `admin/siswa/create` with the list of available kelas.
     */
    public function create(): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/create', [
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    /**
     * Persist a new siswa record together with a freshly-generated login
     * account.
     *
     * Both writes (User, Siswa) are wrapped in a single database transaction
     * so a failure on either side rolls back both. The `username` is the
     * `nis` (mirroring the seeder), `name` is the siswa's display name,
     * and the `password` is the admin-supplied value from the form. The
     * new user is `is_active = true` by default.
     *
     * @param  SiswaRequest  $request  The validated form-request (includes `password`).
     * @return RedirectResponse Redirect to the siswa index with a success flash message containing the new username.
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
                'kelas' => $data['kelas'] ?? null,
            ]);
        });

        return redirect()->route('admin.siswa.index')->with(
            'success',
            "Siswa {$data['nama_siswa']} berhasil ditambahkan. Akun login otomatis dibuat dengan username: {$nis}."
        );
    }

    /**
     * Show the form to edit an existing siswa.
     *
     * Route-model binding resolves the `Siswa` instance from the `nis`
     * URL parameter (see `Siswa::getRouteKeyName()`).
     *
     * @param  Siswa  $siswa  The siswa to edit, resolved by route-model binding.
     * @return Response Inertia response rendering `admin/siswa/edit`.
     */
    public function edit(Siswa $siswa): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();

        return Inertia::render('admin/siswa/edit', [
            'siswa' => $siswa,
            'daftar_kelas' => $daftarKelas,
        ]);
    }

    /**
     * Update an existing siswa record. The `nis` field is immutable: the
     * form-request removes it from the payload on PUT/PATCH.
     *
     * When a non-empty `password` is supplied, the linked login account's
     * password is reset in the same transaction. Passing an empty password
     * leaves the existing password unchanged.
     *
     * @param  SiswaRequest  $request  The validated form-request.
     * @param  Siswa  $siswa  The siswa to update, resolved by route-model binding.
     * @return RedirectResponse Redirect to the siswa index with a success flash message.
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
     * Delete a siswa record. Related `nilai` rows are removed automatically
     * via the database-level `ON DELETE CASCADE` foreign key.
     *
     * @param  Siswa  $siswa  The siswa to delete, resolved by route-model binding.
     * @return RedirectResponse Redirect to the siswa index with a success flash message.
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
