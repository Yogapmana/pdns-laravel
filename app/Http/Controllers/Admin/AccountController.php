<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountRequest;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display the paginated user account list with search and role filters.
     *
     * Eager-loads the linked `siswa` and `guru` profiles (and the guru's
     * mengajar combinations) so the table can be rendered without N+1 queries.
     *
     * @param  Request  $request  Current HTTP request; reads `search` and `role` query parameters.
     * @return Response Inertia response rendering `admin/accounts/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $accounts = User::query()
            ->with(['siswa:nis,user_id,nama_siswa,kelas', 'guru:id,user_id,nama_guru', 'guru.mengajar:id_guru,kelas,mata_pelajaran'])
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
     * Show the form to create a new user account, listing siswa and guru
     * records that do not yet have a linked account.
     *
     * @return Response Inertia response rendering `admin/accounts/create`.
     */
    public function create(): Response
    {
        $siswa = Siswa::whereNull('user_id')
            ->orderBy('nama_siswa')
            ->get(['nis', 'nama_siswa', 'kelas']);

        $guru = Guru::whereNull('user_id')
            ->with('mengajar:id_guru,kelas,mata_pelajaran')
            ->orderBy('nama_guru')
            ->get();

        return Inertia::render('admin/accounts/create', [
            'siswa_tanpa_akun' => $siswa,
            'guru_tanpa_akun' => $guru,
        ]);
    }

    /**
     * Persist a new user account, optionally linking it to an existing
     * siswa or guru profile (depending on the chosen role).
     *
     * @param  AccountRequest  $request  The validated form-request.
     * @return RedirectResponse Redirect to the accounts index with a success flash message.
     */
    public function store(AccountRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        if ($data['role'] === User::ROLE_SISWA && ! empty($data['nis'])) {
            Siswa::where('nis', $data['nis'])->update(['user_id' => $user->id]);
        }

        if ($data['role'] === User::ROLE_GURU && ! empty($data['guru_id'])) {
            Guru::where('id', $data['guru_id'])->update(['user_id' => $user->id]);
        }

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil dibuat.');
    }

    /**
     * Toggle the `is_active` flag of a user account.
     *
     * Refuses with an error flash if the target account is the currently
     * authenticated user (prevents admins from locking themselves out).
     *
     * @param  User  $user  The target account, resolved by route-model binding.
     * @return RedirectResponse Redirect back with a success or error flash message.
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
     * Reset the password of a user account.
     *
     * @param  Request  $request  Current HTTP request; reads the new `password` field (min 6 chars).
     * @param  User  $user  The target account, resolved by route-model binding.
     * @return RedirectResponse Redirect back with a success flash message naming the affected username.
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
