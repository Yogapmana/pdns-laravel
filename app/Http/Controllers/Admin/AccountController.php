<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Menampilkan daftar akun pengguna ter-paginasi dengan filter pencarian dan role.
     *
     * Eager-load profil `siswa` dan `guru` yang ditautkan (serta kombinasi mengajar guru)
     * agar tabel dapat dirender tanpa query N+1.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca parameter kueri `search` dan `role`.
     * @return Response Respon Inertia yang merender view `admin/accounts/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $accounts = User::query()
            ->with([
                'siswa:nis,user_id,nama_siswa',
                'guru:id,user_id,nama_guru',
            ])
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->when($role, fn ($q) => $q->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/accounts/index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Menampilkan form untuk membuat akun admin baru.
     *
     * Hanya role `admin` yang dapat dibuat dari form ini. Akun guru dan siswa
     * dibuat secara otomatis sebagai bagian dari alur Tambah Guru / Tambah Siswa masing-masing.
     *
     * @return Response Respon Inertia yang merender view `admin/accounts/create-admin`.
     */
    public function showCreateAdmin(): Response
    {
        return Inertia::render('admin/accounts/create-admin');
    }

    /**
     * Menyimpan akun admin baru. Admin memasukkan `username`,
     * `name`, dan `password` secara langsung (tidak dibuat otomatis).
     *
     * @param  Request  $request  Request HTTP saat ini; membaca `username`, `name`, `password`, dan `password_confirmation`.
     * @return RedirectResponse Pengalihan (redirect) ke indeks akun dengan pesan sukses flash.
     */
    public function createAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(6), 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? null,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.accounts.index')->with(
            'success',
            "Akun admin {$user->username} berhasil dibuat."
        );
    }

    /**
     * Mengaktifkan/menonaktifkan status `is_active` dari suatu akun pengguna.
     *
     * Menolak dengan pesan kesalahan flash jika akun target adalah pengguna yang saat ini
     * sedang login (mencegah admin mengunci akun mereka sendiri).
     *
     * @param  User  $user  Akun target, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan kembali dengan pesan flash sukses atau error.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun berhasil {$status}.");
    }

    /**
     * Mereset password akun pengguna.
     *
     * @param  Request  $request  Request HTTP saat ini; membaca kolom password baru (minimal 6 karakter).
     * @param  User  $user  Akun target, di-resolve oleh route-model binding.
     * @return RedirectResponse Pengalihan kembali dengan pesan flash sukses yang menyebutkan username yang bersangkutan.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', "Password untuk {$user->username} berhasil direset.");
    }
}
